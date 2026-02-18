import {defineConfig} from 'vite'
import {viteStaticCopy} from 'vite-plugin-static-copy'
import path from "path";
import fs from "fs";

// https://vitejs.dev/config/

//Add All css and js here
//Important: Key must be output filepath without extension, and value will be the file source
const inputs = [
    'resources/elementor/product-variation-select-control.js',
    'resources/elementor/product-carousel-elementor.js',
    'resources/elementor/product-select-control.js',
];

let viteConfig;

const moveManifestPlugin = {
    name: "move-manifest",
    configResolved(resolvedConfig) {
        viteConfig = resolvedConfig;
    },
    writeBundle() {
        const outDir = viteConfig.build.outDir;
        const manifestSrc = path.join(outDir, ".vite", "manifest.json");
        const manifestDest = path.resolve(__dirname, 'assets/manifest.json');

        console.log({
            manifestSrc,
            manifestDest
        })
        const viteDir = path.join(outDir, ".vite");

        if (fs.existsSync(manifestSrc)) {
            // Move the manifest file
            fs.renameSync(manifestSrc, manifestDest);

            // Remove empty .vite directory if exists
            if (fs.existsSync(viteDir) && fs.readdirSync(viteDir).length === 0) {
                fs.rmSync(viteDir, {recursive: true});
            }

            // Read manifest.json content
            const manifestContent = JSON.parse(fs.readFileSync(manifestDest, "utf8"));

            // Convert JSON to PHP array string
            const phpArray = jsonToPhpArray(manifestContent);

            // Update config/app.php
            const configPath = path.resolve(__dirname, "config/vite_config.php");
            //create if not exists
            if (!fs.existsSync(configPath)) {
                fs.writeFileSync(configPath, '<?php return [];', "utf8");
            }
            let configData = fs.readFileSync(configPath, "utf8");

            // Replace or insert 'manifest' key

            fs.writeFileSync(configPath, '<?php return ' + phpArray + ';', "utf8");
            console.log("✅ Manifest array injected into config/vite_config.php");
        }
    },
};


// Helper function to convert JSON to PHP array syntax
function jsonToPhpArray(obj, indentLevel = 1) {
    const indent = "    ".repeat(indentLevel);
    if (Array.isArray(obj)) {
        return "[\n" +
            obj.map(v => `${indent}${valueToPhp(v, indentLevel + 1)}`).join(",\n") +
            "\n" + "    ".repeat(indentLevel - 1) + "]";
    } else if (typeof obj === "object" && obj !== null) {
        return "[\n" +
            Object.entries(obj)
                .map(([key, value]) => `${indent}'${key}' => ${valueToPhp(value, indentLevel + 1)}`)
                .join(",\n") +
            "\n" + "    ".repeat(indentLevel - 1) + "]";
    }
    return valueToPhp(obj, indentLevel);
}

function valueToPhp(value, indentLevel) {
    if (typeof value === "string") {
        return `'${value.replace(/'/g, "\\'")}'`;
    } else if (typeof value === "number") {
        return String(value);
    } else if (typeof value === "boolean") {
        return value ? "true" : "false";
    } else if (value === null) {
        return "null";
    } else if (Array.isArray(value) || typeof value === "object") {
        return jsonToPhpArray(value, indentLevel);
    }
    return "null";
}
export default defineConfig({
    plugins:
        [
            viteStaticCopy({
                targets: [
                    {src: 'resources/images', dest: ''},
                ]
            }),
            moveManifestPlugin
        ],

    build: {
        manifest: true,
        outDir: 'assets',
        //assetsDir: '',
        publicDir: 'assets',
        //root: '/',
        emptyOutDir: true, // delete the contents of the output directory before each build

        // https://rollupjs.org/guide/en/#big-list-of-options
        rollupOptions: {
            input: inputs,
            output: {
                chunkFileNames: '[name].js',
                entryFileNames: '[name].js',
            },
        },
    },

    resolve: {
        alias: {
            'vue': 'vue/dist/vue.esm-bundler.js',
            '@': path.resolve(__dirname, 'resources/admin'),
        },
    },

    server: {
        port: 4230,
        strictPort: true,
        cors: {
            origin: '*',
            methods: ['GET'],
            allowedHeaders: ['Content-Type', 'Authorization'],
        },
        hmr: {
            port: 4230,
            host: 'localhost',
            protocol: 'ws',
        }
    }
})
