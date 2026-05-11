const https = require('https');

module.exports = function handler(req, res) {
  if (req.method !== 'POST') {
    res.status(405).json({ error: 'Method not allowed' });
    return;
  }

  let body = '';
  req.on('data', chunk => { body += chunk; });
  req.on('end', () => {
    let email;
    try { email = JSON.parse(body).email; } catch (e) {}
    if (!email) { res.status(400).json({ error: 'Email requis' }); return; }

    const payload = JSON.stringify({ email, listIds: [6], updateEnabled: true });
    const options = {
      hostname: 'api.brevo.com',
      path: '/v3/contacts',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'api-key': process.env.BREVO_API_KEY,
        'Content-Length': Buffer.byteLength(payload),
      },
    };

    const request = https.request(options, response => {
      let data = '';
      response.on('data', chunk => { data += chunk; });
      response.on('end', () => {
        const status = response.statusCode;
        if (status === 200 || status === 201 || status === 204) {
          res.status(200).json({ success: true });
        } else {
          try { res.status(status).json(JSON.parse(data)); }
          catch (e) { res.status(status).json({ error: data }); }
        }
      });
    });

    request.on('error', () => res.status(500).json({ error: 'Erreur serveur' }));
    request.write(payload);
    request.end();
  });
};
