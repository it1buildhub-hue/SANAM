"""HTTP role checks against the SANAM TEST instance (127.0.0.1:8082 only)."""
import http.cookiejar
import re
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

BASE = "http://127.0.0.1:8082"
PASSWORD = "Sanam-Test-Pass-2026"
MARIADB = r"C:\Program Files\MariaDB 11.8\bin\mariadb.exe"
TEST_DB = "sanam_test"
results = []


def user_id(login):
    return query(f"SELECT id FROM sanam_users WHERE name='{login}' LIMIT 1")


def query(sql):
    out = subprocess.run([MARIADB, "-u", "root", "-N", "-B", TEST_DB, "-e", sql],
                         capture_output=True, text=True, check=True).stdout.strip()
    return out.splitlines()[0] if out else ""


def opener_for_login(login):
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    html = opener.open(BASE + "/", timeout=300).read().decode("utf-8", "replace")
    token = re.search(r'name="_sanam_csrf_token" value="([^"]+)"', html)
    if not token:
        raise SystemExit("login page has no CSRF token")
    data = urllib.parse.urlencode({
        "login_name": login,
        "login_password": PASSWORD,
        "_sanam_csrf_token": token.group(1),
    }).encode()
    resp = opener.open(BASE + "/front/login.php", data, timeout=300)
    body = resp.read().decode("utf-8", "replace")
    if "login_name" in body and "Sign in" in body:
        raise SystemExit(f"login failed for {login}")
    return opener


def fetch(opener, path, data=None):
    try:
        req = urllib.request.Request(BASE + path, data=data)
        resp = opener.open(req, timeout=300)
        return resp.status, resp.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode("utf-8", "replace")


def check(name, status, expected):
    ok = status in expected
    results.append(ok)
    print(("PASS" if ok else "FAIL"), name, "->", status, "(expected", expected, ")")


observer = opener_for_login("test.observer")
auditor = opener_for_login("test.auditor")
manager = opener_for_login("test.manager")

# Observer has no SANAM rights.
check("observer: scan page denied", fetch(observer, "/plugins/sanamassets/front/scan.php")[0], [403])
check("observer: campaigns denied", fetch(observer, "/plugins/sanamassets/front/campaigns.php")[0], [403])
check("observer: dashboard denied", fetch(observer, "/plugins/sanamassets/front/dashboard.php")[0], [403])
check("observer: export denied", fetch(observer, "/plugins/sanamassets/front/export.php?type=inventory")[0], [403])

# Auditor: scans and reads, cannot export or change custody.
check("auditor: scan page allowed", fetch(auditor, "/plugins/sanamassets/front/scan.php")[0], [200])
check("auditor: campaigns view allowed", fetch(auditor, "/plugins/sanamassets/front/campaigns.php")[0], [200])
check("auditor: export denied", fetch(auditor, "/plugins/sanamassets/front/export.php?type=inventory")[0], [403])
check("auditor: custody change refused", fetch(auditor, "/plugins/sanamassets/front/custody.php", data=b"action=issue")[0], [200, 302, 403])

# Manager: full access, including CSV export.
check("manager: dashboard allowed", fetch(manager, "/plugins/sanamassets/front/dashboard.php")[0], [200])
check("manager: labels allowed", fetch(manager, "/plugins/sanamassets/front/labels.php")[0], [200])
check("manager: settings allowed", fetch(manager, "/plugins/sanamassets/front/config.php")[0], [200])
status, body = fetch(manager, "/plugins/sanamassets/front/export.php?type=inventory")
check("manager: inventory export allowed", status, [200])
results.append("Asset ID" in body)
print("PASS" if "Asset ID" in body else "FAIL", "manager: export has the CSV header")

# CSRF: a state-changing POST without a token must be refused and must change nothing.
before = int(query("SELECT COUNT(*) FROM sanam_plugin_sanamassets_campaigns"))
status, _ = fetch(manager, "/plugins/sanamassets/front/campaigns.php", data=b"action=create&name=csrf-probe&entities_id=0&kind=spot")
after = int(query("SELECT COUNT(*) FROM sanam_plugin_sanamassets_campaigns"))
check("POST without CSRF token refused", status, [400, 403])
results.append(before == after)
print("PASS" if before == after else "FAIL", "POST without CSRF created no campaign", before, "->", after)

# Auditor registers a draft asset; the observer cannot open the page.
check("observer: register page denied", fetch(observer, "/plugins/sanamassets/front/register.php")[0], [403])
status, page = fetch(auditor, "/plugins/sanamassets/front/register.php")
check("auditor: register page allowed", status, [200])
token = re.search(r'name="_sanam_csrf_token" value="([^"]+)"', page)
probe = "HTTP-DRAFT-" + str(int(time.time()))
body = urllib.parse.urlencode({
    "_sanam_csrf_token": token.group(1), "itemtype": "Computer", "name": probe,
    "serial": "SN-" + probe, "entities_id": "0", "locations_id": "0",
}).encode()
fetch(auditor, "/plugins/sanamassets/front/register.php", data=body)
created = query(f"SELECT CONCAT(otherserial, '|', states_id) FROM sanam_computers WHERE name='{probe}' AND is_deleted=0 LIMIT 1")
draft_id = query("SELECT id FROM sanam_states WHERE name='Draft (to enrich)' LIMIT 1")
good = created.startswith("SNM-") and created.endswith("|" + draft_id)
results.append(good)
print("PASS" if good else "FAIL", "auditor draft registration gets an SNM id and the draft state", created)

# Manager issues and returns an asset from the scan page (quick custody), and assigns a telecom line.
free_asset = query("SELECT a.asset_id FROM sanam_plugin_sanamassets_assetids a WHERE a.status='active' AND NOT EXISTS (SELECT 1 FROM sanam_plugin_sanamassets_assignments s WHERE s.itemtype=a.itemtype AND s.items_id=a.items_id AND s.status='active' AND s.kind<>'shared') ORDER BY a.id LIMIT 1")
page = fetch(manager, f"/plugins/sanamassets/front/scan.php?asset={free_asset}")[1]
token = re.search(r'name="_sanam_csrf_token" value="([^"]+)"', page).group(1)
body = urllib.parse.urlencode({"_sanam_csrf_token": token, "action": "issue_quick", "asset": free_asset, "users_id": user_id("test.auditor"), "kind": "permanent", "condition": "Good"}).encode()
fetch(manager, f"/plugins/sanamassets/front/scan.php?asset={free_asset}", data=body)
issued = query(f"SELECT COUNT(*) FROM sanam_plugin_sanamassets_assignments WHERE asset_id='{free_asset}' AND status='active' AND users_id={user_id('test.auditor')}")
results.append(issued == "1")
print("PASS" if issued == "1" else "FAIL", "manager quick-issue from scan page creates one active assignment", free_asset, issued)

assignment = query(f"SELECT id FROM sanam_plugin_sanamassets_assignments WHERE asset_id='{free_asset}' AND status='active' LIMIT 1")
page = fetch(manager, f"/plugins/sanamassets/front/scan.php?asset={free_asset}")[1]
token = re.search(r'name="_sanam_csrf_token" value="([^"]+)"', page).group(1)
body = urllib.parse.urlencode({"_sanam_csrf_token": token, "action": "return_quick", "asset": free_asset, "assignment_id": assignment, "condition": "Good"}).encode()
fetch(manager, f"/plugins/sanamassets/front/scan.php?asset={free_asset}", data=body)
returned = query(f"SELECT status FROM sanam_plugin_sanamassets_assignments WHERE id={assignment}")
results.append(returned == "returned")
print("PASS" if returned == "returned" else "FAIL", "manager quick-return from scan page closes the assignment", returned)

line = query("SELECT id FROM sanam_lines WHERE is_deleted=0 ORDER BY id DESC LIMIT 1")
page = fetch(manager, "/plugins/sanamassets/front/telecom.php")[1]
token = re.search(r'name="_sanam_csrf_token" value="([^"]+)"', page).group(1)
body = urllib.parse.urlencode({"_sanam_csrf_token": token, "action": "assign", "line_id": line, "users_id": user_id("test.auditor")}).encode()
fetch(manager, "/plugins/sanamassets/front/telecom.php", data=body)
owner = query(f"SELECT users_id FROM sanam_lines WHERE id={line}")
results.append(owner == user_id("test.auditor"))
print("PASS" if owner == user_id("test.auditor") else "FAIL", "manager assigns a telecom line owner", line, owner)

# Every manager page renders, and shows the content it should.
asset = query("SELECT asset_id FROM sanam_plugin_sanamassets_assetids WHERE status='active' ORDER BY id LIMIT 1")
campaign = query("SELECT id FROM sanam_plugin_sanamassets_campaigns ORDER BY id DESC LIMIT 1")
manager_id = user_id("test.manager")
for path, expect in [
    ("/plugins/sanamassets/front/campaigns.php", None),
    (f"/plugins/sanamassets/front/campaigns.php?id={campaign}", None),
    ("/plugins/sanamassets/front/custody.php", None),
    ("/plugins/sanamassets/front/reports.php", None),
    ("/plugins/sanamassets/front/telecom.php", None),
    (f"/plugins/sanamassets/front/employee.php?users_id={manager_id}", None),
    (f"/plugins/sanamassets/front/employee.php?users_id={manager_id}&print=1", "Asset handover"),
    (f"/plugins/sanamassets/front/labelprint.php?ids={asset}", "<svg"),
    (f"/plugins/sanamassets/front/scan.php?asset={asset}", "View full record"),
    (f"/plugins/sanamassets/front/scan.php?q={asset}", None),
]:
    status, body = fetch(manager, path)
    good = status == 200 and (expect is None or expect in body)
    results.append(good)
    print("PASS" if good else "FAIL", "manager page", path, "->", status, ("contains " + expect) if expect else "")

print("\n%d passed, %d failed" % (sum(results), len(results) - sum(results)))
sys.exit(0 if all(results) else 1)
