const TradingParserController = require('./controllers/TradingParserController');
const ClearParser = require('./library/ClearParser');
const util = require('./library/Util');

TradingParserController.scan().then(async function(documentList){
  
  var extract = require('pdf-text-extract');

  var options = {
    ownerPassword: '329'
  }

  var clearParser    = new ClearParser();
  let sessionBalance = 0;

  clearParser.parse(documentList, options);

  setTimeout(function(){
    // console.log(clearParser.tradeNotes);
    // clearParser.tradeNotes.sort();

    const tradeNoteList = {};
    Object.keys(clearParser.tradeNotes).sort().forEach(function(key) {
      tradeNoteList[key] = clearParser.tradeNotes[key];
    });
  
    // console.log('DATA          TRADES    CORRETAGEM    RESULTADO DIA       TAXAS        SALDO    IMPOSTO');

    // for (key in tradeNoteList) {
    //   let tradeNote  = tradeNoteList[key];
    //   console.log(tradeNote.trades.daytrade)
    //   sessionBalance = Math.fround(sessionBalance + tradeNote.balance + tradeNote.resume.totalFee).toFixed(2) * 1.0;
    //   console.log(util.sprintf('% 10s    % 6s    % 10s    % 13s    % 8s    % 9s    % 7s', tradeNote.marketDate, tradeNote.trades.daytrade.length, tradeNote.brokerFee, tradeNote.balance, tradeNote.resume.totalFee, sessionBalance, tradeNote.incomeTax));
    // }

    console.log(clearParser.position)

    // a.sort();
    // console.log(a.join('\n'));
  }, 300);
});