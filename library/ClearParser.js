const moment = require('moment');
const util = require('./Util');
const Trade = require('./Trade');
const TradeNote = require('./TradeNote');
const ParserBase = require('./ParserBase');
var extract = require('pdf-text-extract');

class ClearParser extends ParserBase {

  parseDocument(lineList, filePath) {
  
    let readingMode       = null;
    let currentMarketDate = null;
    let ignoreTradeNote   = false;
  
    lineList.forEach(function(line, index){
  
      if (ignoreTradeNote) {
        return;
      }
  
      if (readingMode == 'header') {
        let header = this.readHeader(line);
  
        // Aqui verificamos se a nota já foi interpretada
        if (header.page == 1) {
          for (let key in this.tradeNotes) {
            if (this.tradeNotes[key].noteNumber == header.noteNumber) {
              ignoreTradeNote = true;
              return;
            }
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
        let trade = this.readDayTrade(line);
  
        if (trade instanceof Trade) {
          this.tradeNotes[currentMarketDate].addTrade(trade);
        } else {
          // Se retornou outra coisa sem ser uma instância da classe Trade então vamos dar como encerrada a leitura de trades dessa página
          readingMode = null;
        }
      }
  
      if (readingMode == 'swingtrade') {
        let trade = this.readSwingTrade(line);
  
        if (trade instanceof Trade) {
  
          if (trade.type == 'swingtrade') {
            trade.date = currentMarketDate;
            this.addPosition(trade);
          }

          this.tradeNotes[currentMarketDate].addTrade(trade);
        } else {
          // Se retornou outra coisa sem ser uma instância da classe Trade então vamos dar como encerrada a leitura de trades dessa página
          readingMode = null;
        }
      }

      if (readingMode && readingMode.match(/dayTradeResume[0-9]/)) {
        let resume;
        switch (readingMode) {
          case 'dayTradeResume1':
            resume = this.readDayTradeResume1(line);
            break;
          case 'dayTradeResume2':
            resume = this.readDayTradeResume2(line);
            break;
          case 'dayTradeResume3':
            resume = this.readDayTradeResume3(line);
            break;
          case 'dayTradeResume4':
            resume = this.readDayTradeResume4(line);
            break;
        }

        for (let key in resume) {
          this.tradeNotes[currentMarketDate].resume[key] = resume[key];
        }

        readingMode = null;
      }


      if (readingMode == 'swingTradeResume') {
        let lineListTmp = [];
        lineListTmp.push(lineList[index+1]);
        lineListTmp.push(lineList[index+2]);
        lineListTmp.push(lineList[index+3]);
        lineListTmp.push(lineList[index+4]);
        lineListTmp.push(lineList[index+5]);
        lineListTmp.push(lineList[index+6]);
        lineListTmp.push(lineList[index+7]);
        lineListTmp.push(lineList[index+8]);
        lineListTmp.push(lineList[index+9]);
        lineListTmp.push(lineList[index+10]);
        lineListTmp.push(lineList[index+12]);
        lineListTmp.push(lineList[index+13]);
        lineListTmp.push(lineList[index+14]);
        lineListTmp.push(lineList[index+15]);
        lineListTmp.push(lineList[index+16]);
        lineListTmp.push(lineList[index+17]);
        lineListTmp.push(lineList[index+18]);
        lineListTmp.push(lineList[index+19]);

        let resume = this.readSwingTradeResume(lineListTmp.join('\n'));
        
        for (let key in resume) {
          this.tradeNotes[currentMarketDate].resume[key] = resume[key];
        }

        readingMode = null;
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

        if (line.match(/Resumo dos Neg.*cios +Resumo Financeiro/i)) {
          readingMode = 'swingTradeResume';
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
      page: util.purify(matches[2]),
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
    
    let balance = value * (balanceType == 'C' ? 1 : -1);
  
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
    
    let brokerFee = 0;//util.toFloat(brokerFee);
    stock = stock.replace(/ +/g, ' ');
    stock = stock.replace(/^(.*) (ON|PN|N2).*/, '$1 $2');
    stock = stock.replace(/ (ED|EJ|EDJ) /, ' ');
    
    if (marketType.match(/OPCAO DE /)) {
      stock = stock.replace(/^[0-9]{1,2}\/[0-9]{1,2} /, '');
    }

    quantity  = util.toFloat(quantity, 2);
    value     = util.toFloat(value, 2);
    price     = util.toFloat(price, 4);
    
    let balance = value * (balanceType == 'C' ? 1 : -1);
  
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

  readSwingTradeResume(line){

    let pattern = '';
    pattern += 'Vendas à vista +(-?[0-9\.,]+) +Valor líquido das operações +(-?[0-9\.,]+) +(C|D)\n';
    pattern += 'Compras à vista +(-?[0-9\.,]+) +Taxa de liquidação +(-?[0-9\.,]+) +(C|D)\n';
    pattern += 'Opções - compras +(-?[0-9\.,]+) +Taxa de Registro +(-?[0-9\.,]+) +(C|D)\n';
    pattern += 'Opções - vendas +(-?[0-9\.,]+) +Total CBLC +(-?[0-9\.,]+) +(C|D)\n';
    pattern += 'Operações à termo +(-?[0-9\.,]+) +Bolsa\n';
    pattern += 'Valor das oper.* +(-?[0-9\.,]+) +Taxa de termo\/opções +(-?[0-9\.,]+) +(C|D)\n';
    pattern += 'Valor das operações +(-?[0-9\.,]+) +Taxa A.N.A. +(-?[0-9\.,]+) +(C|D)\n';
    pattern += ' +Emolumentos +(-?[0-9\.,]+) +(C|D)\n';
    pattern += ' +Total Bovespa / Soma +(-?[0-9\.,]+) +(C|D)\n';
    pattern += 'Especificações diversas +Custos Operacionais\n';
    pattern += ' +Taxa Operacional +(-?[0-9\.,]+) +(C|D)\n';
    pattern += ' +Execução +(-?[0-9\.,]+)\n';
    pattern += 'A coluna Q indica liquidação no Agente do Qualificado. +Taxa de Custódia +(-?[0-9\.,]+)\n';
    pattern += ' +Impostos +(-?[0-9\.,]+)\n';
    pattern += ' +I.R.R.F. s/ operações, base R\\$ *(-?[0-9\.,]+) +(-?[0-9\.,]+)\n';
    pattern += '(IRRF Day Trade: Base R\\$ *(-?[0-9\.,]+) +Projeção R\\$ *(-?[0-9\.,]+))? +Outros +(-?[0-9\.,]+) +(C|D)\n';
    pattern += ' +Total Custos / Despesas +(-?[0-9\.,]+) +(C|D)\n';
    pattern += '.*Observações.*Líquido para.* +(-?[0-9\.,]+) +(C|D)';
    
    let matches = line.match(new RegExp(pattern, 'im'));

    if (!matches) {
      return;
    }
    
    let atSightSalesValue         = util.toFloat(matches[1]);
    let operationsNetValue        = util.toFloat(matches[2]);
    let operationsNetOperation    = matches[3] == 'D' ? -1 : 1;
    let atSightBuysValue          = util.toFloat(matches[4]);
    let settlementFee             = util.toFloat(matches[5]);
    let settlementFeeOperation    = matches[6] == 'D' ? -1 : 1;
    let optionsBuysValue          = util.toFloat(matches[7]);
    let registerFee               = util.toFloat(matches[8]);
    let registerOperation         = matches[9] == 'D' ? -1 : 1;
    let optionsSalesValue         = util.toFloat(matches[10]);
    let cblcValue                 = util.toFloat(matches[11]);
    let cblcOperation             = matches[12] == 'D' ? -1 : 1;
    let termOperationsValue       = util.toFloat(matches[13]);
    let operationsWithTitlesValue = util.toFloat(matches[14]);
    let termOptionsFee            = util.toFloat(matches[15]);
    let termOptionsOperation      = matches[16] == 'D' ? -1 : 1;
    let operationsValue           = util.toFloat(matches[17]);
    let anaFee                    = util.toFloat(matches[18]);
    let anaOperation              = matches[19] == 'D' ? -1 : 1;
    let emolumentValue            = util.toFloat(matches[20]);
    let emolumentOperation        = matches[21] == 'D' ? -1 : 1;
    let bovespaSumValue           = util.toFloat(matches[22]);
    let bovespaSumOperation       = matches[23] == 'D' ? -1 : 1;
    let operationalFee            = util.toFloat(matches[24]);
    let operationalOperation      = matches[25] == 'D' ? -1 : 1;
    let executionFee              = util.toFloat(matches[26]);
    let custodyFee                = util.toFloat(matches[27]);
    let tax                              = util.toFloat(matches[28]);
    let incomeTaxBaseValue               = util.toFloat(matches[30]);
    let incomeTaxDue                     = util.toFloat(matches[31]);
    let dayTradeIncomeTaxBaseValue       = util.toFloat(matches[32]);
    let dayTradeIncomeTaxProjectionValue = util.toFloat(matches[33]);
    let otherValue                       = util.toFloat(matches[34]);
    let otherOperation                   = matches[35] == 'D' ? -1 : 1;
    let totalCostValue                   = util.toFloat(matches[36]);
    let totalCostOperation               = matches[37] == 'D' ? -1 : 1;

    operationsNetValue *= operationsNetOperation;
    settlementFee      *= settlementFeeOperation;
    registerFee        *= registerOperation;
    cblcValue          *= cblcOperation;
    termOptionsFee     *= termOptionsOperation;
    anaFee             *= anaOperation;
    emolumentValue     *= emolumentOperation;
    bovespaSumValue   *= bovespaSumOperation;
    operationalFee     *= operationalOperation;
    otherValue         *= otherOperation;
    totalCostValue     *= totalCostOperation;

    return {
      settlementFee,
      registerFee,
      emolumentValue,
      custodyFee,
      tax,
      otherValue,
      totalCostValue,
      incomeTaxDue,
      dayTradeIncomeTaxProjectionValue
    }
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
}

module.exports = ClearParser;