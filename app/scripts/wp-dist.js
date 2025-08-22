#!/usr/bin/env node
const {execSync} = require('child_process');
const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

function titleCase(str) {
  return str.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function pad(n) {
  return String(n).padStart(2, '0');
}

(async function () {
  try {
    const appDir = path.resolve(__dirname, '..');
    const rootDir = path.resolve(appDir, '..');
    const packageJsonPath = path.join(appDir, 'package.json');
    const pkg = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
    const name = pkg.name;
    const [major, minor, patch] = pkg.version.split('.').map(Number);
    const version = [major, minor, patch + 1].join('.');
    pkg.version = version;
    fs.writeFileSync(packageJsonPath, JSON.stringify(pkg, null, 2) + '\n');
    const pluginName = titleCase(name);

    const envFile = path.join(appDir, 'src', 'environments', 'environment.ts');
    let envContent = fs.readFileSync(envFile, 'utf8');
    const buildDate = new Date();
    const buildStr = `${buildDate.getFullYear()}${pad(buildDate.getMonth() + 1)}${pad(buildDate.getDate())}${pad(buildDate.getHours())}${pad(buildDate.getMinutes())}${pad(buildDate.getSeconds())}`;
    envContent = envContent.replace(/(version:\s*')[^']+(')/, `$1${version}$2`);
    envContent = envContent.replace(/(build:\s*')[^']+(')/, `$1${buildStr}$2`);
    fs.writeFileSync(envFile, envContent);

    execSync('npx ng build --configuration production', {cwd: appDir, stdio: 'inherit'});

    const pluginFile = path.join(rootDir, 'res-pong.php');
    let content = fs.readFileSync(pluginFile, 'utf8');
    content = content.replace(/(\* Plugin Name:\s*).*/, `$1${pluginName}`);
    content = content.replace(/(\* Version:\s*).*/, `$1${version}`);
    content = content.replace(/(define\('RES_PONG_VERSION',\s*RES_PONG_DEV \? time\(\) : ')[^']+('\);)/, `$1${version}$2`);
    fs.writeFileSync(pluginFile, content);

    const releaseDir = path.join(rootDir, 'release');
    fs.mkdirSync(releaseDir, {recursive: true});

    // remove previous zip files
    for (const file of fs.readdirSync(releaseDir)) {
      if (file.endsWith('.zip')) {
        fs.rmSync(path.join(releaseDir, file), {force: true});
      }
    }

    const tempDir = path.join(releaseDir, 'temp');
    fs.rmSync(tempDir, {recursive: true, force: true});
    fs.mkdirSync(tempDir);

    const copy = (src, dest) => fs.cpSync(src, dest, {recursive: true});
    copy(path.join(rootDir, 'assets'), path.join(tempDir, 'assets'));
    copy(path.join(rootDir, 'includes'), path.join(tempDir, 'includes'));

    ['README.md', 'res-pong.php', 'uninstall.php'].forEach(file => {
      const src = path.join(rootDir, file);
      const dest = path.join(tempDir, file);
      fs.copyFileSync(src, dest);
      if (file === 'res-pong.php') {
        let releaseContent = fs.readFileSync(dest, 'utf8');
        releaseContent = releaseContent.replace('define(\'RES_PONG_DEV\', true);', 'define(\'RES_PONG_DEV\', false);');
        fs.writeFileSync(dest, releaseContent);
      }
    });

    const distDir = path.join(appDir, 'dist', 'browser');
    const destAppDir = path.join(tempDir, 'app');
    fs.mkdirSync(destAppDir);
    copy(distDir, destAppDir);

    const finalDir = path.join(releaseDir, 'res-pong');
    fs.rmSync(finalDir, {recursive: true, force: true});
    fs.renameSync(tempDir, finalDir);

    const now = new Date();
    const dateStr = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}_${pad(now.getHours())}-${pad(now.getMinutes())}`;
    const zipName = `${name}-${version}-${dateStr}.zip`;
    const zipPath = path.join(releaseDir, zipName);

    await new Promise((resolve, reject) => {
      const output = fs.createWriteStream(zipPath);
      const archive = archiver('zip', {zlib: {level: 9}});
      output.on('close', () => {
        fs.rmSync(finalDir, {recursive: true, force: true}); // elimina la directory res-pong
        resolve();
      });
      output.on('error', reject);
      archive.on('error', reject);
      archive.directory(finalDir, 'res-pong');
      archive.pipe(output);
      archive.finalize();
    });

    const pluginJsonPath = path.join(releaseDir, 'plugin.json');
    if (fs.existsSync(pluginJsonPath)) {
      const pluginData = JSON.parse(fs.readFileSync(pluginJsonPath, 'utf8'));
      pluginData.version = version;
      pluginData.download_url = pluginData.download_url.replace(/release\/[^/]+\.zip/, `release/${zipName}`);
      const updated = new Date();
      const updatedStr = `${updated.getFullYear()}-${pad(updated.getMonth() + 1)}-${pad(updated.getDate())} ${pad(updated.getHours())}:${pad(updated.getMinutes())}:${pad(updated.getSeconds())}`;
      pluginData.last_updated = updatedStr;
      fs.writeFileSync(pluginJsonPath, JSON.stringify(pluginData, null, 2) + '\n');
    }

    try {
      execSync(`git add ${zipPath}`, {cwd: rootDir, stdio: 'ignore'});
    } catch (e) {
      // ignore git errors
    }
  } catch (err) {
    console.error(err);
    process.exit(1);
  }
})();
