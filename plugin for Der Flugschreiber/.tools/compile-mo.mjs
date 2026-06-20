import fs from 'node:fs';

const [poPath, moPath] = process.argv.slice(2);
const po = fs.readFileSync(poPath, 'utf8');
const entries = [];
let id = null;
let value = null;

for (const line of po.split(/\r?\n/)) {
    const idMatch = line.match(/^msgid "(.*)"$/);
    const valueMatch = line.match(/^msgstr "(.*)"$/);

    if (idMatch) {
        if (id !== null && value !== null && id !== '') entries.push([id, value]);
        id = JSON.parse(`"${idMatch[1]}"`);
        value = null;
    } else if (valueMatch) {
        value = JSON.parse(`"${valueMatch[1]}"`);
    }
}

if (id !== null && value !== null && id !== '') entries.push([id, value]);
entries.push(['', 'Content-Type: text/plain; charset=UTF-8\nLanguage: de_DE\n']);
entries.sort((a, b) => Buffer.from(a[0]).compare(Buffer.from(b[0])));

const count = entries.length;
const headerSize = 28;
const originalsOffset = headerSize;
const translationsOffset = originalsOffset + count * 8;
let stringsOffset = translationsOffset + count * 8;
const originalBuffers = entries.map(([source]) => Buffer.from(source, 'utf8'));
const translationBuffers = entries.map(([, target]) => Buffer.from(target, 'utf8'));
const originalTable = Buffer.alloc(count * 8);
const translationTable = Buffer.alloc(count * 8);
const strings = [];

for (let i = 0; i < count; i++) {
    originalTable.writeUInt32LE(originalBuffers[i].length, i * 8);
    originalTable.writeUInt32LE(stringsOffset, i * 8 + 4);
    strings.push(originalBuffers[i], Buffer.from([0]));
    stringsOffset += originalBuffers[i].length + 1;
}

for (let i = 0; i < count; i++) {
    translationTable.writeUInt32LE(translationBuffers[i].length, i * 8);
    translationTable.writeUInt32LE(stringsOffset, i * 8 + 4);
    strings.push(translationBuffers[i], Buffer.from([0]));
    stringsOffset += translationBuffers[i].length + 1;
}

const header = Buffer.alloc(headerSize);
header.writeUInt32LE(0x950412de, 0);
header.writeUInt32LE(0, 4);
header.writeUInt32LE(count, 8);
header.writeUInt32LE(originalsOffset, 12);
header.writeUInt32LE(translationsOffset, 16);
header.writeUInt32LE(0, 20);
header.writeUInt32LE(0, 24);

fs.writeFileSync(moPath, Buffer.concat([header, originalTable, translationTable, ...strings]));
