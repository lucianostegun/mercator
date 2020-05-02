const moment = require('moment');
const util = require('./Util');
const Trade = require('./Trade');
const TradeNote = require('./TradeNote');
var extract = require('pdf-text-extract');

class ClearParser {

  tradeNotes = [];
  position = {};

  constructor() {
    super();

    initialize();
  }

  parse(documentList, options) {
    
    var self = this;
  
    documentList.forEach(async function(filePath){
      
      if (!filePath.match(/\.pdf/i)) {
        return;
      }
  
      await extract(filePath, options, 'pdftotext', function (err, pages) {
        
        if (err) {
          console.dir(err);
          return;
        }
        
        var lineList = pages.join('').split('\n');
        self.parseDocument(lineList, filePath);
      });
    });
  }
  
  parseDocument(lineList, filePath) {
  
    let readingMode       = null;
    let currentMarketDate = null;
    let ignoreTradeNote   = false;
  
    lineList.forEach(function(line){
  
      if (ignoreTradeNote) {
        return;
      }
  
      if (readingMode == 'header') {
        let header = readHeader(line);
  
        // Aqui verificamos se a nota já foi interpretada
        for (key in this.tradeNotes) {
          if (this.tradeNotes[key].noteNumber == header.noteNumber) {
            ignoreTradeNote = true;
            return;
          }
        }
        
        currentMarketDate = header.marketDate;
        if (!this.tradeNotes.hasOwnProperty(currentMarketDate)) {
          this.tradeNotes[currentMarketDate] = new TradeNote(header.noteNumber, header.marketDate);
        }
  
        readingMode = null;
        return;
      }
  
      if (readingMode == 'daytrade') {
        let trade = readDayTrade(line);
  
        if (trade instanceof Trade) {
          this.tradeNotes[currentMarketDate].addTrade(trade);
        } else {
          // Se retornou outra coisa sem ser uma instância da classe Trade então vamos dar como encerrada a leitura de trades dessa página
          readingMode = null;
        }
      }
  
      if (readingMode == 'swingtrade') {
        let trade = readSwingTrade(line);
  
        if (trade instanceof Trade) {
  
          if (trade.type == 'swingtrade') {
            this.addPosition(trade);
          }
  
          this.tradeNotes[currentMarketDate].addTrade(trade);
        } else {
          // Se retornou outra coisa sem ser uma instância da classe Trade então vamos dar como encerrada a leitura de trades dessa página
          readingMode = null;
        }
      }
  
      if (readingMode == 'dayTradeResume1') {
        let resume = readDayTradeResume1(line);
        for (key in resume) {
          this.tradeNotes[currentMarketDate].resume[key] = resume[key];
        }
        readingMode = null;
        return;
      }
  
      if (readingMode == 'dayTradeResume2') {
        let resume = readDayTradeResume2(line);
        for (key in resume) {
          this.tradeNotes[currentMarketDate].resume[key] = resume[key];
        }
        readingMode = null;
        return;
      }
  
      if (readingMode == 'dayTradeResume3') {
        let resume = readDayTradeResume3(line);
        for (key in resume) {
          this.tradeNotes[currentMarketDate].resume[key] = resume[key];
        }
        readingMode = null;
        return;
      }
  
      if (readingMode == 'dayTradeResume4') {
        let resume = readDayTradeResume4(line);
        for (key in resume) {
          this.tradeNotes[currentMarketDate].resume[key] = resume[key];
        }
        readingMode = null;
        return;
      }
  
      if (!readingMode) {
        if (line.match(/.*Nr.*nota.*Folha.*Data.*preg.*o.*/i)) {
          readingMode = 'header';
          return;
        }
  
        if (line.match(/.*C\/V.*Mercadoria.*Vencimento.*Quantidade.*/i)) {
          readingMode = 'daytrade';
          return;
        }
  
        if (line.match(/.*Q +Negocia.*o +C\/V +Tipo +mercado +Prazo.*/i)) {
          readingMode = 'swingtrade';
          return;
        }
  
        if (line.match(/.*Venda dispon.*vel +Compra dispon.*vel.*/i)) {
          readingMode = 'dayTradeResume1';
          return;
        }
  
        if (line.match(/.*IRRF +IRRF Day Trade/i)) {
          readingMode = 'dayTradeResume2';
          return;
        }
  
        if (line.match(/.*Outros Custos +Impostos +Ajuste de posi.*o/i)) {
          readingMode = 'dayTradeResume3';
          return;
        }
  
        if (line.match(/.*Outros.*IRRF operacional.*Total Conta Investimento/i)) {
          readingMode = 'dayTradeResume4';
          return;
        }
      }
    }.bind(this));
  }
  
  readHeader(line){
  
    let matches = line.match(/ *([0-9\.]+) +([0-9\.]+) +([0-9]{2}\/[0-9]{2}\/[0-9]{4})/);
  
    if (!matches) {
      return false;
    }
  
    return {
      noteNumber: util.purify(matches[1]),
      marketDate: moment(util.purify(matches[3]), 'DD/MM/YYYY').format('YYYY-MM-DD'),
    }
  }
  
  readDayTrade(line){
  
    // Se tentamos ler um trade e a linha está vazia é porque acabaram as linhas de trades
    if (!line.trim()) {
      return false;
    }
  
    let trade = new Trade('daytrade');
  
    let matches = line.match(/^\s*(C|V) +(.*) +@?([0-9]{2}\/[0-9]{2}\/[0-9]{4}) +([0-9]+) +([0-9\-\.,]+) +(DAY TRADE) +([0-9\-\.,]+) +(C|D) +([0-9\-\.,]+)/);
  
    if (!matches) {
      return false;
    }
      
    let exchangeType = util.purify(matches[1]);
    let stock        = util.purify(matches[2]);
    let dueDate      = util.purify(matches[3]);
    let quantity     = util.purify(matches[4]);
    let price        = util.purify(matches[5]);
    let value        = util.purify(matches[7]);
    let balanceType  = util.purify(matches[8]);
    let brokerFee    = util.purify(matches[9]);
  
    brokerFee = util.toFloat(brokerFee);
    quantity  = util.toFloat(quantity, 2);
    value     = util.toFloat(value, 2);
    price     = util.toFloat(price, 4);
    
    balance = value * (balanceType == 'C' ? 1 : -1);
  
    trade.exchangeType = exchangeType;
    trade.stock        = stock;
    trade.dueDate      = dueDate;
    trade.quantity     = quantity;
    trade.price        = price;
    trade.value        = value;
    trade.balanceType  = balanceType;
    trade.brokerFee    = brokerFee;
    trade.balance      = balance;
    
    return trade;
  }
  
  readSwingTrade(line){
  
    // Se tentamos ler um trade e a linha está vazia é porque acabaram as linhas de trades
    if (!line.trim()) {
      return false;
    }
  
    let trade = new Trade('swingtrade');
  
    let market       = util.purify(line.replace(/^(.{25}).*/, '$1'));
    let exchangeType = util.purify(line.replace(/^.{25} *(C|V).*/, '$1'));
    let marketType   = util.purify(line.replace(/^.{25} *(C|V) *([A-Z ]{20}) .*/, '$2'));
    let stock        = util.purify(line.replace(/^.{78}(.{45}).*/, '$1'));
    let comments     = util.purify(line.replace(/^.{81}.{45}(.*) ([\-0-9]+) * ([\-0-9\.\,]+) * ([\-0-9\.\,]+) *(C|D)$/, '$1'));
    let quantity     = util.purify(line.replace(/.* ([\-0-9]+) * ([\-0-9\.\,]+) * ([\-0-9\.\,]+) *(C|D)$/, '$1'));
    let price        = util.purify(line.replace(/.* ([\-0-9\.\,]+) * ([\-0-9\.\,]+) *(C|D)$/, '$1'));
    let value        = util.purify(line.replace(/.* ([\-0-9\.\,]+) *(C|D)$/, '$1'));
    let balanceType  = util.purify(line.replace(/.*(C|D)$/, '$1'));
  
    let matches = line.match(/.*(1-BOVESPA) +(C|V) .*([0-9]+) +(-?[0-9\,\.]+) +(-?[0-9\,\.]+) +(C|D)/);
    
    if (!matches) {
      return false;
    }
    
    stock     = stock.replace(/ +/g, ' ');
    brokerFee = 0;//util.toFloat(brokerFee);
    quantity  = util.toFloat(quantity, 2);
    value     = util.toFloat(value, 2);
    price     = util.toFloat(price, 4);
    
    balance = value * (balanceType == 'C' ? 1 : -1);
  
    trade.market       = market;
    trade.exchangeType = exchangeType;
    trade.marketType   = marketType;
    trade.stock        = stock;
    trade.comments     = comments.split('');
    trade.quantity     = quantity;
    trade.price        = price;
    trade.value        = value;
    trade.balanceType  = balanceType;
    trade.balance      = balance;
    
    return trade;
  }
  
  readDayTradeResume1(line){
  
    let matches = line.match(/ *([0-9\-\.,]+) +([0-9\-\.,]+) +([0-9\-\.,]+) +([0-9\-\.,]+) +([0-9\-\.,]+) +\| +(C|D)/);
  
    if (!matches) {
      return false;
    }
  
    let tradeBalance = matches[5] == 'C' ? 1 : -1;
      
    return {
      availableSale: util.toFloat(util.purify(matches[1])),
      availableBuy: util.toFloat(util.purify(matches[2])),
      optionsBuy: util.toFloat(util.purify(matches[3])),
      optionsSale: util.toFloat(util.purify(matches[4])),
      tradeValue: util.toFloat(util.purify(matches[5])) * tradeBalance,
    }
  }
  
  readDayTradeResume2(line){
  
    let matches = line.match(/ *([0-9\-\.,]+) *\| *(C|D)? +([0-9\-\.,]+) +([0-9\-\.,]+) +([0-9\-\.,]+) +([0-9\-\.,]+) +\| *(C|D)/);
  
    if (!matches) {
      return false;
    }
  
    let incomeTaxOperation = matches[2] == 'D' ? -1 : 1;
    let tradeOperation     = matches[7] == 'D' ? -1 : 1;
      
  
    return {
      incomeTax: util.toFloat(util.purify(matches[1])) * incomeTaxOperation,
      incomeTaxDue: util.toFloat(util.purify(matches[3])) * -1,
      brokerTax: util.toFloat(util.purify(matches[4])) * -1,
      exchangeTax: util.toFloat(util.purify(matches[5])) * -1,
      tradeValue: util.toFloat(util.purify(matches[6])) * tradeOperation,
    }
  }
  
  readDayTradeResume3(line){
  
    let matches = line.match(/ *([0-9\-\.,]+) +([0-9\-\.,]+) +([0-9\-\.,]+) *\| *(C|D)? +([0-9\-\.,]+) *\| *(C|D)? +([0-9\-\.,]+) *\| *(C|D)/);
  
    if (!matches) {
      return false;
    }
  
    let positionAdjustOperation = matches[4] == 'D' ? -1 : 1;
    let dayTradeAdjustOperation = matches[6] == 'D' ? -1 : 1;
    let totalFeeOperation       = matches[8] == 'D' ? -1 : 1;
      
    return {
      otherCosts: util.toFloat(util.purify(matches[1])) * -1,
      tax: util.toFloat(util.purify(matches[2])) * -1,
      positionAdjust: util.toFloat(util.purify(matches[3])) * positionAdjustOperation,
      dayTradeAdjust: util.toFloat(util.purify(matches[5])) * dayTradeAdjustOperation,
      totalFee: util.toFloat(util.purify(matches[7])) * totalFeeOperation,
    }
  }
  
  readDayTradeResume4(line){
  
    let matches = line.match(/ *([0-9\-\.,]+) +([0-9\-\.,]+) +([0-9\-\.,]+) *\| *(C|D)? +([0-9\-\.,]+) *\| *(C|D)? +([0-9\-\.,]+) *\| *(C|D)? +([0-9\-\.,]+) *\| *(C|D)?/);
  
    if (!matches) {
      return false;
    }
  
    let totalInvestmentAccountOperation = matches[4] == 'D' ? -1 : 1;
    let totalRegularAccountOperation    = matches[6] == 'D' ? -1 : 1;
    let totalNetOperation               = matches[8] == 'D' ? -1 : 1;
    let totalNetNoteOperation           = matches[10] == 'D' ? -1 : 1;
      
    return {
      other: util.toFloat(util.purify(matches[1])) * -1,
      operationalIncomeFee: util.toFloat(util.purify(matches[2])) * -1,
      totalInvesmentAccount: util.toFloat(util.purify(matches[3])) * totalInvestmentAccountOperation,
      totalRegularAccount: util.toFloat(util.purify(matches[5])) * totalRegularAccountOperation,
      totalNet: util.toFloat(util.purify(matches[7])) * totalNetOperation,
      totalNetNote: util.toFloat(util.purify(matches[9])) * totalNetNoteOperation,
    }
  }
  
  addPosition(trade) {
    let stock = trade.stock;
  
    if (!this.position.hasOwnProperty(stock)) {
      this.position[stock] = {
        quantity: 0,
        value: 0,
        price: 0
      }
    }
  
    let updatePrice = true;
      
    if (trade.exchangeType == 'C') {
      
      // Se estiver vendido e estiver comprando alguma de volta não vamos atualizar o preço médio
      if (this.position[stock].quantity < 0) {
        updatePrice = false;
      }
      
      this.position[stock].quantity = this.position[stock].quantity + trade.quantity;
      this.position[stock].value    = this.position[stock].value + (trade.price * trade.quantity);
    } else {
      
      // Se estiver comprado e estiver vendendo alguma de volta não vamos atualizar o preço médio
      if (this.position[stock].quantity > 0) {
        updatePrice = false;
      }
       
      this.position[stock].quantity = this.position[stock].quantity + trade.quantity;
      this.position[stock].value    = this.position[stock].value + (trade.price * trade.quantity);
    }
    
    if (this.position[stock].quantity == 0) {
      delete this.position[stock];
      return;
    }
    
    if (updatePrice) {
      this.position[stock].price = this.position[stock].value / this.position[stock].quantity;
    }
  }
}
// var ClearParser = function() {
//   this.tradeNotes = [];
//   this.position = {};

//   return this;
// }

// ClearParser.prototype.parse = function(documentList, options) {
  
//   var self = this;

//   documentList.forEach(async function(filePath){
    
//     if (!filePath.match(/\.pdf/i)) {
//       return;
//     }

//     await extract(filePath, options, 'pdftotext', function (err, pages) {
      
//       if (err) {
//         console.dir(err);
//         return;
//       }
      
//       var lineList = pages.join('').split('\n');
//       self.parseDocument(lineList, filePath);
//     });
//   });
// }

// ClearParser.prototype.parseDocument = function(lineList, filePath) {

//   let readingMode       = null;
//   let currentMarketDate = null;
//   let ignoreTradeNote   = false;

//   lineList.forEach(function(line){

//     if (ignoreTradeNote) {
//       return;
//     }

//     if (readingMode == 'header') {
//       let header = readHeader(line);

//       // Aqui verificamos se a nota já foi interpretada
//       for (key in this.tradeNotes) {
//         if (this.tradeNotes[key].noteNumber == header.noteNumber) {
//           ignoreTradeNote = true;
//           return;
//         }
//       }
      
//       currentMarketDate = header.marketDate;
//       if (!this.tradeNotes.hasOwnProperty(currentMarketDate)) {
//         this.tradeNotes[currentMarketDate] = new TradeNote(header.noteNumber, header.marketDate);
//       }

//       readingMode = null;
//       return;
//     }

//     if (readingMode == 'daytrade') {
//       let trade = readDayTrade(line);

//       if (trade instanceof Trade) {
//         this.tradeNotes[currentMarketDate].addTrade(trade);
//       } else {
//         // Se retornou outra coisa sem ser uma instância da classe Trade então vamos dar como encerrada a leitura de trades dessa página
//         readingMode = null;
//       }
//     }

//     if (readingMode == 'swingtrade') {
//       let trade = readSwingTrade(line);

//       if (trade instanceof Trade) {

//         if (trade.type == 'swingtrade') {
//           this.addPosition(trade);
//         }

//         this.tradeNotes[currentMarketDate].addTrade(trade);
//       } else {
//         // Se retornou outra coisa sem ser uma instância da classe Trade então vamos dar como encerrada a leitura de trades dessa página
//         readingMode = null;
//       }
//     }

//     if (readingMode == 'dayTradeResume1') {
//       let resume = readDayTradeResume1(line);
//       for (key in resume) {
//         this.tradeNotes[currentMarketDate].resume[key] = resume[key];
//       }
//       readingMode = null;
//       return;
//     }

//     if (readingMode == 'dayTradeResume2') {
//       let resume = readDayTradeResume2(line);
//       for (key in resume) {
//         this.tradeNotes[currentMarketDate].resume[key] = resume[key];
//       }
//       readingMode = null;
//       return;
//     }

//     if (readingMode == 'dayTradeResume3') {
//       let resume = readDayTradeResume3(line);
//       for (key in resume) {
//         this.tradeNotes[currentMarketDate].resume[key] = resume[key];
//       }
//       readingMode = null;
//       return;
//     }

//     if (readingMode == 'dayTradeResume4') {
//       let resume = readDayTradeResume4(line);
//       for (key in resume) {
//         this.tradeNotes[currentMarketDate].resume[key] = resume[key];
//       }
//       readingMode = null;
//       return;
//     }

//     if (!readingMode) {
//       if (line.match(/.*Nr.*nota.*Folha.*Data.*preg.*o.*/i)) {
//         readingMode = 'header';
//         return;
//       }

//       if (line.match(/.*C\/V.*Mercadoria.*Vencimento.*Quantidade.*/i)) {
//         readingMode = 'daytrade';
//         return;
//       }

//       if (line.match(/.*Q +Negocia.*o +C\/V +Tipo +mercado +Prazo.*/i)) {
//         readingMode = 'swingtrade';
//         return;
//       }

//       if (line.match(/.*Venda dispon.*vel +Compra dispon.*vel.*/i)) {
//         readingMode = 'dayTradeResume1';
//         return;
//       }

//       if (line.match(/.*IRRF +IRRF Day Trade/i)) {
//         readingMode = 'dayTradeResume2';
//         return;
//       }

//       if (line.match(/.*Outros Custos +Impostos +Ajuste de posi.*o/i)) {
//         readingMode = 'dayTradeResume3';
//         return;
//       }

//       if (line.match(/.*Outros.*IRRF operacional.*Total Conta Investimento/i)) {
//         readingMode = 'dayTradeResume4';
//         return;
//       }
//     }
//   }.bind(this));
// }

// const readHeader = function(line){

//   let matches = line.match(/ *([0-9\.]+) +([0-9\.]+) +([0-9]{2}\/[0-9]{2}\/[0-9]{4})/);

//   if (!matches) {
//     return false;
//   }

//   return {
//     noteNumber: util.purify(matches[1]),
//     marketDate: moment(util.purify(matches[3]), 'DD/MM/YYYY').format('YYYY-MM-DD'),
//   }
// }

// const readDayTrade = function(line){

//   // Se tentamos ler um trade e a linha está vazia é porque acabaram as linhas de trades
//   if (!line.trim()) {
//     return false;
//   }

//   let trade = new Trade('daytrade');

//   let matches = line.match(/^\s*(C|V) +(.*) +@?([0-9]{2}\/[0-9]{2}\/[0-9]{4}) +([0-9]+) +([0-9\-\.,]+) +(DAY TRADE) +([0-9\-\.,]+) +(C|D) +([0-9\-\.,]+)/);

//   if (!matches) {
//     return false;
//   }
    
//   let exchangeType = util.purify(matches[1]);
//   let stock        = util.purify(matches[2]);
//   let dueDate      = util.purify(matches[3]);
//   let quantity     = util.purify(matches[4]);
//   let price        = util.purify(matches[5]);
//   let value        = util.purify(matches[7]);
//   let balanceType  = util.purify(matches[8]);
//   let brokerFee    = util.purify(matches[9]);

//   brokerFee = util.toFloat(brokerFee);
//   quantity  = util.toFloat(quantity, 2);
//   value     = util.toFloat(value, 2);
//   price     = util.toFloat(price, 4);
  
//   balance = value * (balanceType == 'C' ? 1 : -1);

//   trade.exchangeType = exchangeType;
//   trade.stock        = stock;
//   trade.dueDate      = dueDate;
//   trade.quantity     = quantity;
//   trade.price        = price;
//   trade.value        = value;
//   trade.balanceType  = balanceType;
//   trade.brokerFee    = brokerFee;
//   trade.balance      = balance;
  
//   return trade;
// }

// const readSwingTrade = function(line){

//   // Se tentamos ler um trade e a linha está vazia é porque acabaram as linhas de trades
//   if (!line.trim()) {
//     return false;
//   }

//   let trade = new Trade('swingtrade');

//   let market       = util.purify(line.replace(/^(.{25}).*/, '$1'));
//   let exchangeType = util.purify(line.replace(/^.{25} *(C|V).*/, '$1'));
//   let marketType   = util.purify(line.replace(/^.{25} *(C|V) *([A-Z ]{20}) .*/, '$2'));
//   let stock        = util.purify(line.replace(/^.{78}(.{45}).*/, '$1'));
//   let comments     = util.purify(line.replace(/^.{81}.{45}(.*) ([\-0-9]+) * ([\-0-9\.\,]+) * ([\-0-9\.\,]+) *(C|D)$/, '$1'));
//   let quantity     = util.purify(line.replace(/.* ([\-0-9]+) * ([\-0-9\.\,]+) * ([\-0-9\.\,]+) *(C|D)$/, '$1'));
//   let price        = util.purify(line.replace(/.* ([\-0-9\.\,]+) * ([\-0-9\.\,]+) *(C|D)$/, '$1'));
//   let value        = util.purify(line.replace(/.* ([\-0-9\.\,]+) *(C|D)$/, '$1'));
//   let balanceType  = util.purify(line.replace(/.*(C|D)$/, '$1'));

//   let matches = line.match(/.*(1-BOVESPA) +(C|V) .*([0-9]+) +(-?[0-9\,\.]+) +(-?[0-9\,\.]+) +(C|D)/);
  
//   if (!matches) {
//     return false;
//   }
  
//   stock     = stock.replace(/ +/g, ' ');
//   brokerFee = 0;//util.toFloat(brokerFee);
//   quantity  = util.toFloat(quantity, 2);
//   value     = util.toFloat(value, 2);
//   price     = util.toFloat(price, 4);
  
//   balance = value * (balanceType == 'C' ? 1 : -1);

//   trade.market       = market;
//   trade.exchangeType = exchangeType;
//   trade.marketType   = marketType;
//   trade.stock        = stock;
//   trade.comments     = comments.split('');
//   trade.quantity     = quantity;
//   trade.price        = price;
//   trade.value        = value;
//   trade.balanceType  = balanceType;
//   trade.balance      = balance;
  
//   return trade;
// }

// const readDayTradeResume1 = function(line){

//   let matches = line.match(/ *([0-9\-\.,]+) +([0-9\-\.,]+) +([0-9\-\.,]+) +([0-9\-\.,]+) +([0-9\-\.,]+) +\| +(C|D)/);

//   if (!matches) {
//     return false;
//   }

//   let tradeBalance = matches[5] == 'C' ? 1 : -1;
    
//   return {
//     availableSale: util.toFloat(util.purify(matches[1])),
//     availableBuy: util.toFloat(util.purify(matches[2])),
//     optionsBuy: util.toFloat(util.purify(matches[3])),
//     optionsSale: util.toFloat(util.purify(matches[4])),
//     tradeValue: util.toFloat(util.purify(matches[5])) * tradeBalance,
//   }
// }

// const readDayTradeResume2 = function(line){

//   let matches = line.match(/ *([0-9\-\.,]+) *\| *(C|D)? +([0-9\-\.,]+) +([0-9\-\.,]+) +([0-9\-\.,]+) +([0-9\-\.,]+) +\| *(C|D)/);

//   if (!matches) {
//     return false;
//   }

//   let incomeTaxOperation = matches[2] == 'D' ? -1 : 1;
//   let tradeOperation     = matches[7] == 'D' ? -1 : 1;
    

//   return {
//     incomeTax: util.toFloat(util.purify(matches[1])) * incomeTaxOperation,
//     incomeTaxDue: util.toFloat(util.purify(matches[3])) * -1,
//     brokerTax: util.toFloat(util.purify(matches[4])) * -1,
//     exchangeTax: util.toFloat(util.purify(matches[5])) * -1,
//     tradeValue: util.toFloat(util.purify(matches[6])) * tradeOperation,
//   }
// }

// const readDayTradeResume3 = function(line){

//   let matches = line.match(/ *([0-9\-\.,]+) +([0-9\-\.,]+) +([0-9\-\.,]+) *\| *(C|D)? +([0-9\-\.,]+) *\| *(C|D)? +([0-9\-\.,]+) *\| *(C|D)/);

//   if (!matches) {
//     return false;
//   }

//   let positionAdjustOperation = matches[4] == 'D' ? -1 : 1;
//   let dayTradeAdjustOperation = matches[6] == 'D' ? -1 : 1;
//   let totalFeeOperation       = matches[8] == 'D' ? -1 : 1;
    
//   return {
//     otherCosts: util.toFloat(util.purify(matches[1])) * -1,
//     tax: util.toFloat(util.purify(matches[2])) * -1,
//     positionAdjust: util.toFloat(util.purify(matches[3])) * positionAdjustOperation,
//     dayTradeAdjust: util.toFloat(util.purify(matches[5])) * dayTradeAdjustOperation,
//     totalFee: util.toFloat(util.purify(matches[7])) * totalFeeOperation,
//   }
// }

// const readDayTradeResume4 = function(line){

//   let matches = line.match(/ *([0-9\-\.,]+) +([0-9\-\.,]+) +([0-9\-\.,]+) *\| *(C|D)? +([0-9\-\.,]+) *\| *(C|D)? +([0-9\-\.,]+) *\| *(C|D)? +([0-9\-\.,]+) *\| *(C|D)?/);

//   if (!matches) {
//     return false;
//   }

//   let totalInvestmentAccountOperation = matches[4] == 'D' ? -1 : 1;
//   let totalRegularAccountOperation    = matches[6] == 'D' ? -1 : 1;
//   let totalNetOperation               = matches[8] == 'D' ? -1 : 1;
//   let totalNetNoteOperation           = matches[10] == 'D' ? -1 : 1;
    
//   return {
//     other: util.toFloat(util.purify(matches[1])) * -1,
//     operationalIncomeFee: util.toFloat(util.purify(matches[2])) * -1,
//     totalInvesmentAccount: util.toFloat(util.purify(matches[3])) * totalInvestmentAccountOperation,
//     totalRegularAccount: util.toFloat(util.purify(matches[5])) * totalRegularAccountOperation,
//     totalNet: util.toFloat(util.purify(matches[7])) * totalNetOperation,
//     totalNetNote: util.toFloat(util.purify(matches[9])) * totalNetNoteOperation,
//   }
// }

// ClearParser.prototype.addPosition = function(trade) {
//   let stock = trade.stock;

//   if (!this.position.hasOwnProperty(stock)) {
//     this.position[stock] = {
//       quantity: 0,
//       value: 0,
//       price: 0
//     }
//   }

//   let updatePrice = true;
    
//   if (trade.exchangeType == 'C') {
    
//     // Se estiver vendido e estiver comprando alguma de volta não vamos atualizar o preço médio
//     if (this.position[stock].quantity < 0) {
//       updatePrice = false;
//     }
    
//     this.position[stock].quantity = this.position[stock].quantity + trade.quantity;
//     this.position[stock].value    = this.position[stock].value + (trade.price * trade.quantity);
//   } else {
    
//     // Se estiver comprado e estiver vendendo alguma de volta não vamos atualizar o preço médio
//     if (this.position[stock].quantity > 0) {
//       updatePrice = false;
//     }
     
//     this.position[stock].quantity = this.position[stock].quantity + trade.quantity;
//     this.position[stock].value    = this.position[stock].value + (trade.price * trade.quantity);
//   }
  
//   if (this.position[stock].quantity == 0) {
//     delete this.position[stock];
//     return;
//   }
  
//   if (updatePrice) {
//     this.position[stock].price = this.position[stock].value / this.position[stock].quantity;
//   }
// }

module.exports = ClearParser;