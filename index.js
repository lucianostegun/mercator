const TradingParserController = require('./controllers/TradingParserController');

TradingParserController.scan().then(function(a){
  console.log(a)
});