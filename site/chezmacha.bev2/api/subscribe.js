export default async function handler(req, res) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  const { email } = req.body;
  if (!email) return res.status(400).json({ error: 'Email requis' });

  const response = await fetch('https://api.brevo.com/v3/contacts', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'api-key': process.env.BREVO_API_KEY,
    },
    body: JSON.stringify({ email, listIds: [6], updateEnabled: true }),
  });

  const status = response.status;
  if (status === 200 || status === 201 || status === 204) {
    return res.status(200).json({ success: true });
  }

  const data = await response.json().catch(() => ({}));
  return res.status(status).json(data);
}
