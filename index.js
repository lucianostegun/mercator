const TradingParserController = require('./controllers/TradingParserController');
const ClearParser = require('./library/ClearParser');
const util = require('./library/Util');

TradingParserController.scan().then(async function(documentList){
  
  var extract = require('pdf-text-extract');

  var options = {
    ownerPassword: '329'
  }

  var clearParser    = new ClearParser();
  
  await clearParser.parse(documentList, options);

  const tradeNoteList = {};
  Object.keys(clearParser.tradeNotes).sort().forEach(function(key) {
    tradeNoteList[key] = clearParser.tradeNotes[key];
  });

  console.log('DATA          TRADES    CORRETAGEM    RESULTADO DIA       TAXAS    IMPOSTOS        SALDO');
  console.log('----------------------------------------------------------------------------------------');
  let totalTrades       = 0;
  let totalBrokerFee    = 0;
  let totalBalance      = 0;
  let totalFee          = 0;
  let totalIncomeTaxDue = 0;
  let sessionBalance    = 0;

  for (let key in tradeNoteList) {
    let tradeNote  = tradeNoteList[key];

    if (tradeNote.trades.daytrade.length == 0) {
      continue;
    }
    
    totalTrades       += tradeNote.trades.daytrade.length;
    totalBrokerFee    = Math.fround(totalBrokerFee + tradeNote.brokerFee).toFixed(2) * 1.00;
    totalBalance      = Math.fround(totalBalance + tradeNote.balance).toFixed(2) * 1.00;
    totalFee          = Math.fround(totalFee + tradeNote.resume.totalFee).toFixed(2) * 1.00;
    totalIncomeTaxDue = Math.fround(totalIncomeTaxDue + tradeNote.resume.incomeTaxDue).toFixed(2) * 1.00;
    sessionBalance = Math.fround(sessionBalance + tradeNote.balance + tradeNote.resume.totalFee + tradeNote.resume.incomeTaxDue).toFixed(2) * 1.0;
    console.log(util.sprintf('% 10s    % 6s    % 10s    % 13s    % 8s    % 8s    % 9s', tradeNote.marketDate, tradeNote.trades.daytrade.length, tradeNote.brokerFee, tradeNote.balance, tradeNote.resume.totalFee, tradeNote.resume.incomeTaxDue, sessionBalance));
  }
  console.log('----------------------------------------------------------------------------------------');
  console.log(util.sprintf('% 10s    % 6s    % 10s    % 13s    % 8s    % 8s    % 9s', '', totalTrades, totalBrokerFee, totalBalance, totalFee, totalIncomeTaxDue, sessionBalance));

  console.log('');
  console.log('');

  console.log('ATIVO                  QUANTIDADE       PREÇO        VALOR');
  console.log('----------------------------------------------------------');
  const positionList = {};
  Object.keys(clearParser.position).sort().forEach(function(key) {
    positionList[key] = clearParser.position[key];
  });

  for (stock in positionList) {
    let position = positionList[stock];
    console.log(util.sprintf('%- 15s        % 10s       % 5s        % 5s', stock, position.quantity, position.price, position.value));
  }

    // a.sort();
    // console.log(a.join('\n'));
  // }, 1500);
});