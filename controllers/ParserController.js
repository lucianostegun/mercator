const path = require('path');
const fs = require('fs');

const ParserController = require('../controllers/ParserController');
const NovaFuturaParser = require('../library/NovaFuturaParser');
// const ClearParser = require('../library/ClearParser');
const util = require('../library/Util');

const scan = function (req, res) {
  let directoryPath = req.query.path;
  // directoryPath = '/Users/lucianostegun/Sites/Mercator/web/files/uploads';
  directoryPath = '/Users/lucianostegun/Projetos/Mercator/uploads/Ivana/2021/Nova Futura';

  new Promise(function (resolve, reject) {
    let documentList = [];

    fs.readdir(directoryPath, 'UTF-8', function (err, files) {
      if (err) {
        reject(err);
      } else {
        files.forEach(function (file) {
          documentList.push(directoryPath + '/' + file);
        });

        resolve(documentList);
      }
    });
  }).then(async function (documentList) {
    console.log(documentList);

    var options = {
      ownerPassword: '329',
    };

    // var parser = new ClearParser();
    var parser = new NovaFuturaParser();

    parser.parse(documentList, options).then(function () {
      const tradeNoteList = {};
      Object.keys(parser.tradeNotes)
        .sort()
        .forEach(function (key) {
          tradeNoteList[key] = parser.tradeNotes[key];
        });

      console.log('-------------------------------------- SWING TRADE -------------------------------------');

      let totalTrades = 0;
      let totalBrokerFee = 0;
      let totalBalance = 0;
      let totalFee = 0;
      let totalIncomeTaxDue = 0;
      let sessionBalance = 0;

      let result = {
        daytrade: {
          history: {},
          resume: {},
        },
        swingtrade: {},
      };

      for (let key in tradeNoteList) {
        let tradeNote = tradeNoteList[key];

        if (tradeNote.trades.swingtrade.length == 0) {
          continue;
        }

        totalTrades += tradeNote.trades.swingtrade.length;
        totalBrokerFee = Math.fround(totalBrokerFee + tradeNote.brokerFee).toFixed(2) * 1.0;
        totalBalance = Math.fround(totalBalance + tradeNote.balance).toFixed(2) * 1.0;
        totalFee = Math.fround(totalFee + tradeNote.resume.totalFee).toFixed(2) * 1.0;
        totalIncomeTaxDue = Math.fround(totalIncomeTaxDue + tradeNote.resume.incomeTaxDue).toFixed(2) * 1.0;
        sessionBalance = Math.fround(sessionBalance + tradeNote.balance + tradeNote.resume.totalFee + tradeNote.resume.incomeTaxDue).toFixed(2) * 1.0;

        // RESUMO POR DIA
        // console.log('DATA          TRADES    CORRETAGEM    RESULTADO DIA       TAXAS    IMPOSTOS        SALDO');
        // console.log('----------------------------------------------------------------------------------------');
        // console.log(util.sprintf('% 10s    % 6s    % 10s    % 13s    % 8s    % 8s    % 9s', tradeNote.marketDate, tradeNote.trades.swingtrade.length, tradeNote.brokerFee, tradeNote.balance, tradeNote.resume.totalFee, tradeNote.resume.incomeTaxDue, sessionBalance));

        // console.log('-----------------------------------------------------------------------------------------------------------------------');
        // console.log('DATA          C/V     ATIVO                      QTD         PRECO            VALOR');
        // console.log('------------------------------------------------------------------------------------------------------');

        tradeNote.trades.swingtrade.forEach(function (trade) {
          console.log(
            util.sprintf(
              '%- 10s    %- 7s %- 20s    % 6s    % 10s    % 13s',
              tradeNote.marketDate,
              trade.exchangeType,
              trade.stock,
              trade.quantity,
              trade.price,
              trade.value
            )
          );
        });

        // console.log('----------------------------------------------------------------------------------------');
        // result.swingtrade.history[tradeNote.marketDate] = {
        //   trades: tradeNote.trades.daytrade.length,
        //   brokerFee: tradeNote.brokerFee,
        //   dayBalance: tradeNote.balance,
        //   totalFee: tradeNote.resume.totalFee,
        //   incomeTaxDue: tradeNote.resume.incomeTaxDue,
        //   balance: sessionBalance
        // }
      }

      console.log('\n\n\n');

      console.log('--------------------------------------- DAY TRADE --------------------------------------');
      console.log('DATA          TRADES    CORRETAGEM    RESULTADO DIA       TAXAS    IMPOSTOS        SALDO');
      console.log('----------------------------------------------------------------------------------------');

      totalTrades = 0;
      totalBrokerFee = 0;
      totalBalance = 0;
      totalFee = 0;
      totalIncomeTaxDue = 0;
      sessionBalance = 0;

      for (let key in tradeNoteList) {
        let tradeNote = tradeNoteList[key];

        if (tradeNote.trades.daytrade.length == 0) {
          continue;
        }

        totalTrades += tradeNote.trades.daytrade.length;
        totalBrokerFee = Math.fround(totalBrokerFee + tradeNote.brokerFee).toFixed(2) * 1.0;
        totalBalance = Math.fround(totalBalance + tradeNote.balance).toFixed(2) * 1.0;
        totalFee = Math.fround(totalFee + tradeNote.resume.totalFee).toFixed(2) * 1.0;
        totalIncomeTaxDue = Math.fround(totalIncomeTaxDue + tradeNote.resume.incomeTaxDue).toFixed(2) * 1.0;
        let exchangeTax = 0; //tradeNote.resume.exchangeTax;
        let tradeValue = 0; //tradeNote.resume.tradeValue;
        sessionBalance =
          Math.fround(sessionBalance + tradeNote.balance + tradeNote.resume.totalFee + tradeNote.resume.incomeTaxDue + exchangeTax + tradeValue).toFixed(2) *
          1.0;
        dayBalance = Math.fround(tradeNote.balance + tradeNote.resume.totalFee + tradeNote.resume.incomeTaxDue + exchangeTax + tradeValue).toFixed(2) * 1.0;

        // console.log(tradeNote.resume);
        console.log(
          util.sprintf(
            '% 10s    % 6s    % 10s    % 13s    % 8s    % 8s    % 9s',
            tradeNote.marketDate,
            tradeNote.trades.daytrade.length,
            tradeNote.brokerFee,
            tradeNote.balance,
            (tradeNote.resume.totalFee + exchangeTax + tradeValue).toFixed(2),
            tradeNote.resume.incomeTaxDue,
            dayBalance
          )
        );

        result.daytrade.history[tradeNote.marketDate] = {
          trades: tradeNote.trades.daytrade.length,
          brokerFee: tradeNote.brokerFee,
          dayBalance: tradeNote.balance,
          totalFee: tradeNote.resume.totalFee,
          incomeTaxDue: tradeNote.resume.incomeTaxDue,
          balance: sessionBalance,
        };
      }

      result.daytrade.resume = {
        trades: totalTrades,
        brokerFee: totalBrokerFee,
        bruteBalance: totalBalance,
        totalFee,
        incomeTaxDue: totalIncomeTaxDue,
        balance: sessionBalance,
      };
      console.log('----------------------------------------------------------------------------------------');
      console.log(
        util.sprintf(
          '% 10s    % 6s    % 10s    % 13s    % 8s    % 8s    % 9s',
          '',
          totalTrades,
          totalBrokerFee,
          totalBalance,
          totalFee,
          totalIncomeTaxDue,
          sessionBalance
        )
      );

      console.log('');
      console.log('');

      console.log('ATIVO                  QUANTIDADE       PREÇO        VALOR');
      console.log('----------------------------------------------------------');
      const positionList = {};
      Object.keys(parser.position)
        .sort()
        .forEach(function (key) {
          positionList[key] = parser.position[key];
        });

      for (stock in positionList) {
        let position = positionList[stock];
        console.log(util.sprintf('%- 15s        % 10s       % 5s        % 5s', stock, position.quantity, position.price, position.value));

        result.swingtrade[stock] = {
          quantity: position.quantity,
          price: position.price,
          value: position.value,
        };
      }

      // res.json({status: 'success', path: directoryPath, data: result});
    });
  });
};

module.exports = {
  scan,
};
