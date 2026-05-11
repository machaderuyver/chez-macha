module.exports = async function handler(req, res) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  const email = req.body?.email;
  if (!email) return res.status(400).json({ error: 'Email requis' });

  try {
    const r = await fetch('https://api.brevo.com/v3/contacts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'api-key': process.env.BREVO_API_KEY,
      },
      body: JSON.stringify({ email, listIds: [6], updateEnabled: true }),
    });

    if (r.status === 200 || r.status === 201 || r.status === 204) {
      return res.status(200).json({ success: true });
    }
    const data = await r.json().catch(() => ({}));
    return res.status(r.status).json(data);
  } catch (err) {
    return res.status(500).json({ error: err.message });
  }
};
