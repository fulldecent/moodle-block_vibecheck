#!/usr/bin/env node
// Moodle stylelint requires four-space indentation in stylesheets.
// Prettier writes that indent. This check fails a pull request that drifts back to two spaces.

import { readdirSync, readFileSync, statSync } from "node:fs";
import { join } from "node:path";

/**
 * @param {string} dir
 * @param {string[]} found
 * @returns {string[]}
 */
function cssFiles(dir, found = []) {
  for (const name of readdirSync(dir)) {
    if (name === "node_modules" || name === ".git" || name === "dist") {
      continue;
    }
    const path = join(dir, name);
    if (statSync(path).isDirectory()) {
      cssFiles(path, found);
    } else if (name.endsWith(".css")) {
      found.push(path);
    }
  }
  return found;
}

let failed = false;
for (const path of cssFiles(".")) {
  const lines = readFileSync(path, "utf8").split(/\n/);
  lines.forEach((line, index) => {
    if (line.trim() === "") {
      return;
    }
    if (line.startsWith("\t")) {
      console.error(`${path}:${index + 1}: tab indent`);
      failed = true;
      return;
    }
    const spaces = line.match(/^ */)[0].length;
    if (spaces % 4 !== 0) {
      console.error(`${path}:${index + 1}: indent is ${spaces} spaces`);
      failed = true;
    }
  });
}

if (failed) {
  process.exit(1);
}
