var express = require('express');
var router = express.Router();
const ParserController = require('../controllers/ParserController');

/* GET home page. */
router.get('/scan', ParserController.scan);

module.exports = router;
