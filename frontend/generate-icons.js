import sharp from 'sharp';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const svgPath = path.join(__dirname, 'logo_eyepub.svg');
const publicDir = path.join(__dirname, 'public');
const backendPublicDir = path.join(__dirname, '../backend/public');

async function generate() {
    console.log('🎨 Generating PWA Icons...');
    
    if (!fs.existsSync(svgPath)) {
        console.error('❌ Error: logo.svg not found!');
        process.exit(1);
    }

    const sizes = [192, 512];
    
    for (const size of sizes) {
        const fileName = `pwa-${size}x${size}.png`;
        const outputPath = path.join(publicDir, fileName);
        const backendPath = path.join(backendPublicDir, fileName);
        
        try {
            await sharp(svgPath)
                .resize(size, size)
                .png()
                .toFile(outputPath);
                
            console.log(`✅ Created: ${outputPath}`);
            
            // Copy to backend/public if it exists (for immediate use)
            if (fs.existsSync(backendPublicDir)) {
                 fs.copyFileSync(outputPath, backendPath);
                 console.log(`   ↳ Copied to: ${backendPath}`);
            }
        } catch (error) {
            console.error(`❌ Error generating ${fileName}:`, error);
        }
    }
    console.log('🎉 Done!');
}

generate();
