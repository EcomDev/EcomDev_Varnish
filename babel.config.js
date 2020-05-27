const testPreset = [
    '@babel/preset-env',
    {
        targets: {
            node: 'current',
        },
    },
];

const prodPreset = [
    '@babel/preset-env',
    {
        "targets": "> 0.25%, not dead",
    }
];

module.exports = api => {
    const isTest = api.env('test');
    return {
        presets: [
            isTest ? testPreset : prodPreset
        ]
    };
};