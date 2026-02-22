// cek-gemini.js
const https = require("https");

function cekGeminiAPI(apiKey) {
  return new Promise((resolve) => {
    const data = JSON.stringify({
      contents: [
        {
          parts: [{ text: "Test" }]
        }
      ]
    });

    const options = {
      hostname: "generativelanguage.googleapis.com",
      path: `/v1/models/gemini-2.5-flash:generateContent?key=${apiKey}`,
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Content-Length": data.length
      }
    };

    const req = https.request(options, (res) => {
      let body = "";

      res.on("data", (chunk) => {
        body += chunk;
      });

      res.on("end", () => {
        if (res.statusCode === 200) {
          console.log("✅ API KEY AKTIF & VALID");
        } else if (res.statusCode === 401 || res.statusCode === 403) {
          console.log("❌ API KEY TIDAK VALID / EXPIRED");
        } else {
          console.log(`⚠️ Response Code: ${res.statusCode}`);
          console.log(body);
        }
        resolve();
      });
    });

    req.on("error", (error) => {
      console.log("❌ Terjadi error:", error.message);
      resolve();
    });

    req.write(data);
    req.end();
  });
}

// Ambil API key dari argument CMD
const apiKey = process.argv[2];

if (!apiKey) {
  console.log("Gunakan: node cek-gemini.js API_KEY_KAMU");
} else {
  cekGeminiAPI(apiKey);
}
