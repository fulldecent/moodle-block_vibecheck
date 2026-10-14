#!/usr/bin/env node
// English language strings use sentence case. Proper nouns and the plugin name stay capitalised.

import { readFileSync } from "node:fs";
import { join } from "node:path";

const allow = new Set([
  "Study",
  "companion",
  "Pomodoro",
  "Dashboard",
  "Moodle",
  "Boost",
  "YouTube",
  "GOOD",
  "BAD",
  "I",
]);

const path = join("lang", "en", "block_vibecheck.php");
const text = readFileSync(path, "utf8");
const pattern = /\$string\['([^']+)'\]\s*=\s*'((?:\\'|[^'])*)'/g;
let failed = false;
let match;
while ((match = pattern.exec(text)) !== null) {
  const id = match[1];
  const value = match[2].replace(/\\'/g, "'");
  const sentences = value.split(/(?<=[.!?])\s+/);
  for (const sentence of sentences) {
    const words = sentence.split(/\s+/);
    words.forEach((word, index) => {
      const core = word.replace(/^[^A-Za-z0-9]+|[^A-Za-z0-9]+$/g, "");
      if (core === "" || allow.has(core) || index === 0) {
        return;
      }
      if (/^[A-Z][a-z]/.test(core)) {
        console.error(`${path}: ${id} uses title case in "${value}" ("${core}")`);
        failed = true;
      }
    });
  }
}

if (failed) {
  process.exit(1);
}
