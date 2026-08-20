import { execSync } from 'child_process';
import https from 'https';

const GEMINI_API_KEY = process.env.GEMINI_API_KEY;

if (!GEMINI_API_KEY) {
  console.log('⚠️ [Gemini Reviewer] GEMINI_API_KEY tidak dikonfigurasi. Melewati pengecekan kualitas kode AI.');
  process.exit(0);
}

// 1. Get git diff of the changes
let gitDiff = '';
try {
  // Diff changes from the latest commit (HEAD~1 to HEAD)
  gitDiff = execSync('git diff HEAD~1 HEAD', { encoding: 'utf8' }).trim();
} catch (error) {
  console.log('⚠️ [Gemini Reviewer] Gagal membaca git diff. Melewati review.');
  process.exit(0);
}

if (!gitDiff) {
  console.log('✅ [Gemini Reviewer] Tidak ada perbedaan kode terdeteksi pada commit terbaru.');
  process.exit(0);
}

console.log('🤖 [Gemini Reviewer] Mengirimkan kode perubahan ke Gemini AI untuk review kualitas...');

// 2. Prepare payload for Gemini API
const prompt = `Anda bertindak sebagai Senior Software Architect & Code Reviewer. 
Analisis Git Diff berikut untuk menilai kualitas kode, kepatuhan arsitektur Laravel (PSR-12, clean layers), potensi bug, dan celah keamanan (seperti SQL injection, IDOR, unvalidated file uploads).

Ketentuan Output Review:
- Mulai ulasan Anda dengan ringkasan singkat.
- Jika Anda menemukan kesalahan kritis atau celah keamanan serius, awali baris tersebut dengan tag "[CRITICAL]" di bagian awal feedback Anda.
- Jika tidak ada masalah kritis dan kode aman/bagus, akhiri ulasan Anda dengan kata "[PASSED]" di baris terakhir.

Berikut adalah Git Diff untuk direview:
\`\`\`diff
${gitDiff}
\`\`\`
`;

const postData = JSON.stringify({
  contents: [{
    parts: [{ text: prompt }]
  }]
});

const options = {
  hostname: 'generativelanguage.googleapis.com',
  path: `/v1beta/models/gemini-1.5-flash:generateContent?key=${GEMINI_API_KEY}`,
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Content-Length': Buffer.byteLength(postData)
  }
};

// 3. Make HTTP request to Gemini API
const req = https.request(options, (res) => {
  let data = '';

  res.on('data', (chunk) => {
    data += chunk;
  });

  res.on('end', () => {
    try {
      const responseJson = JSON.parse(data);
      const reviewText = responseJson.candidates?.[0]?.content?.parts?.[0]?.text;

      if (!reviewText) {
        console.error('❌ [Gemini Reviewer] Gagal mendapatkan respons review dari Gemini API.');
        console.error('Raw Response:', data);
        process.exit(0); // Do not block build on API error
      }

      console.log('\n=========================================');
      console.log('📝 HASIL EVALUASI KODE OLEH GEMINI AI:');
      console.log('=========================================');
      console.log(reviewText);
      console.log('=========================================\n');

      if (reviewText.includes('[CRITICAL]')) {
        console.error('❌ [Gemini Reviewer] Ditemukan celah keamanan atau bug kritis. Menghentikan merge!');
        process.exit(1);
      } else if (reviewText.includes('[PASSED]') || reviewText.toLowerCase().includes('passed')) {
        console.log('✅ [Gemini Reviewer] Kualitas kode disetujui. Build Lulus.');
        process.exit(0);
      } else {
        console.log('⚠️ [Gemini Reviewer] Review selesai dengan beberapa catatan kecil. Lulus.');
        process.exit(0);
      }

    } catch (e) {
      console.error('❌ [Gemini Reviewer] Gagal memproses data respons API.', e);
      process.exit(0);
    }
  });
});

req.on('error', (e) => {
  console.error(`❌ [Gemini Reviewer] Gagal menghubungi API: ${e.message}`);
  process.exit(0);
});

req.write(postData);
req.end();
