const fs = require("fs");

function updateVersion() {
  fs.readFile("./public/version.js", "utf8", (err, content) => {
    if (err) throw err;

    // Extract the version object from the JavaScript file
    const versionMatch = content.match(/self\.version\s*=\s*(\{.*?\});/);
    if (!versionMatch || !versionMatch[1]) {
      throw new Error("Could not parse version object from file");
    }

    try {
      // Parse the version object
      const version = JSON.parse(versionMatch[1]);
      version.revision += 1; // Increment the revision number

      // Create the new file content
      const newContent = `self.version = ${JSON.stringify(version)};`;

      fs.writeFile("./public/version.js", newContent, "utf8", (err) => {
        if (err) throw err;
        console.log(`Updated version to ${version.major}.${version.minor}.${version.revision}`);
      });
    } catch (parseError) {
      throw new Error(`Failed to parse version object: ${parseError.message}`);
    }
  });
}

updateVersion();
