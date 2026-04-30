// ========================================
// PROMPEE SHOP - BACKEND SERVER
// Express + SQLite email storage
// ========================================

const express = require('express');
const cors = require('cors');
const Database = require('better-sqlite3');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3000;

// ========================================
// DATABASE SETUP
// ========================================
const dataDir = path.join(__dirname, 'data');
if (!fs.existsSync(dataDir)) {
  fs.mkdirSync(dataDir, { recursive: true });
}

const dbPath = path.join(dataDir, 'emails.db');
const db = new Database(dbPath);

// Create emails table if not exists
db.exec(`
  CREATE TABLE IF NOT EXISTS emails (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    product TEXT NOT NULL,
    amount REAL NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  )
`);

console.log('Database initialized at:', dbPath);

// ========================================
// MIDDLEWARE
// ========================================
app.use(cors());
app.use(express.json());

// Serve static files from parent directory (Main_Page)
app.use(express.static(path.join(__dirname, '..')));

// Serve Payment_path at /pricing/ route
app.use('/pricing', express.static(path.join(__dirname, '../../Payment_path')));

// Handle /pricing/ → serve pricing.html
app.get('/pricing/', (req, res) => {
  res.sendFile(path.join(__dirname, '../../Payment_path/pricing.html'));
});

// ========================================
// API ROUTES
// ========================================

// POST /api/emails - Store a new email
app.post('/api/emails', (req, res) => {
  const { email, product, amount } = req.body;

  // Validate input
  if (!email || !product || !amount) {
    return res.status(400).json({
      error: 'Missing required fields: email, product, amount'
    });
  }

  // Basic email validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    return res.status(400).json({
      error: 'Invalid email format'
    });
  }

  // Insert into database
  try {
    const stmt = db.prepare(`
      INSERT INTO emails (email, product, amount)
      VALUES (?, ?, ?)
    `);

    const info = stmt.run(email, product, parseFloat(amount));

    console.log(`Stored email: ${email} for ${product} (€${amount})`);

    res.status(201).json({
      success: true,
      id: info.lastInsertRowid,
      message: 'Email stored successfully'
    });

  } catch (error) {
    console.error('Database error:', error);
    res.status(500).json({
      error: 'Failed to store email'
    });
  }
});

// GET /api/emails - List all stored emails (admin)
app.get('/api/emails', (req, res) => {
  try {
    const emails = db.prepare('SELECT * FROM emails ORDER BY created_at DESC').all();

    res.json({
      success: true,
      count: emails.length,
      data: emails
    });

  } catch (error) {
    console.error('Database error:', error);
    res.status(500).json({
      error: 'Failed to retrieve emails'
    });
  }
});

// GET /api/emails/stats - Quick stats endpoint
app.get('/api/emails/stats', (req, res) => {
  try {
    const totalEmails = db.prepare('SELECT COUNT(*) as count FROM emails').get();
    const totalRevenue = db.prepare('SELECT SUM(amount) as revenue FROM emails').get();
    const byProduct = db.prepare(`
      SELECT product, COUNT(*) as count, SUM(amount) as revenue
      FROM emails
      GROUP BY product
    `).all();

    res.json({
      success: true,
      totalEmails: totalEmails.count,
      totalRevenue: totalRevenue.revenue || 0,
      byProduct: byProduct
    });

  } catch (error) {
    console.error('Stats error:', error);
    res.status(500).json({
      error: 'Failed to retrieve stats'
    });
  }
});

// ========================================
// SERVE FRONTEND FOR ALL OTHER ROUTES
// ========================================
app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, '..', 'index.html'));
});

// ========================================
// START SERVER
// ========================================
app.listen(PORT, () => {
  console.log(`
╔══════════════════════════════════════════╗
║     PROMPEE SHOP SERVER RUNNING          ║
╠══════════════════════════════════════════╣
║  Frontend: http://localhost:${PORT}        ║
║  API:      http://localhost:${PORT}/api    ║
║  Database: ${dbPath}                      ║
╚══════════════════════════════════════════╝
  `);
});
