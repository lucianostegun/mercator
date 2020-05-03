class TradeNote {
  noteNumber = null;
  marketDate = null;
  trades     = {
    daytrade: [],
    swingtrade: []
  }
  position   = {};
  brokerFee  = 0;
  balance    = 0;
  incomeTax  = 0;
  resume     = {
    availableSale: 0,
    availableBuy: 0,
    optionsBuy: 0,
    optionsSale: 0,
    tradeValue: 0,
    incomeTax: 0,
    incomeTaxDue: 0,
    brokerTax: 0,
    exchangeTax: 0,
    tradeValue: 0,
    otherCosts: 0,
    tax: 0,
    positionAdjust: 0,
    dayTradeAdjust: 0,
    totalFee: 0,
    other: 0,
    operationalIncomeFee: 0,
    totalInvesmentAccount: 0,
    totalRegularAccount: 0,
    totalNet: 0,
    totalNetNote: 0,

    settlementFee: 0,
    registerFee: 0,
    emolumentValue: 0,
    custodyFee: 0,
    otherValue: 0,
    totalCostValue: 0,
    dayTradeIncomeTaxProjectionValue: 0
  }

  constructor(noteNumber, marketDate) {
    this.noteNumber = noteNumber;
    this.marketDate = marketDate;
  }

  addTrade(trade) {
  
    if (trade.type == 'swingtrade' && trade.comments.indexOf('D') != -1) {
      trade.type = 'daytrade';
    }

    if (trade.type == 'daytrade') {
      this.brokerFee = this.brokerFee + trade.brokerFee;
      this.balance   = this.balance + trade.balance;
    
      this.brokerFee = Math.fround(this.brokerFee).toFixed(2) * 1;
      this.balance   = Math.fround(this.balance).toFixed(2) * 1;
    }

    this.trades[trade.type].push(trade);
  }
}

// const TradeNote = function(noteNumber, marketDate) {
//   this.noteNumber = noteNumber;
//   this.marketDate = marketDate;
//   this.trades     = {
//     daytrade: [],
//     swingtrade: []
//   }
//   this.position   = {};
//   this.brokerFee  = 0;
//   this.balance    = 0;
//   this.incomeTax  = 0;
//   this.resume     = {
//     availableSale: 0,
//     availableBuy: 0,
//     optionsBuy: 0,
//     optionsSale: 0,
//     tradeValue: 0,
//     incomeTax: 0,
//     incomeTaxDue: 0,
//     brokerTax: 0,
//     exchangeTax: 0,
//     tradeValue: 0,
//     otherCosts: 0,
//     tax: 0,
//     positionAdjust: 0,
//     dayTradeAdjust: 0,
//     totalFee: 0,
//     other: 0,
//     operationalIncomeFee: 0,
//     totalInvesmentAccount: 0,
//     totalRegularAccount: 0,
//     totalNet: 0,
//     totalNetNote: 0,
//   }

//   return this;
// }

// TradeNote.prototype.addTrade = function(trade) {
  
//   if (trade.type == 'swingtrade' && trade.comments.indexOf('D') != -1) {
//     trade.type = 'daytrade';
//   }

//   if (trade.type == 'daytrade') {
//     this.brokerFee = this.brokerFee + trade.brokerFee;
//     this.balance   = this.balance + trade.balance;
  
//     this.brokerFee = Math.fround(this.brokerFee).toFixed(2) * 1;
//     this.balance   = Math.fround(this.balance).toFixed(2) * 1;
//   }

//   this.trades[trade.type].push(trade);
// }

module.exports = TradeNote;