import sanamEslintConfig from '../../eslint.config.mjs';

export default [
    ...sanamEslintConfig,
    {
        ignores: [
            'node_modules/*',
            'vendor/*',
        ],
    }
];
