var extract = require('pdf-text-extract');

class ParserBase {

  tradeNotes = [];
  position = {};

  constructor() {
  }

  parse(documentList, options) {
    
    var self = this;
    
    var promise = documentList.map(async function(filePath){
        
        if (!filePath.match(/\.pdf/i)) {
          return;
        }
  
        var pages = await extract(filePath, options, 'pdftotext', function(){});
        var lineList = pages.join('').split('\n');
        await self.parseDocument(lineList, filePath);
      });

    return Promise.all(promise);
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

    // if (stock == 'VALID ON') {
    //   console.log(trade.date + ' - ' + trade.exchangeType + ' ' + trade.quantity + ' ' + trade.price);
    // }
  
    let updatePrice = true;
      
    if (trade.exchangeType == 'C') {
      
      // Se estiver vendido e estiver comprando alguma de volta não vamos atualizar o preço médio
      if (this.position[stock].quantity < 0) {
        updatePrice = false;
      }
      
      this.position[stock].quantity = this.position[stock].quantity + trade.quantity;
      this.position[stock].value    = Math.fround(this.position[stock].value + (trade.price * trade.quantity)).toFixed(2) * 1.00;
    } else {
      
      // Se estiver comprado e estiver vendendo alguma de volta não vamos atualizar o preço médio
      if (this.position[stock].quantity > 0) {
        updatePrice = false;
      }
       
      this.position[stock].quantity = this.position[stock].quantity - trade.quantity;
      this.position[stock].value    = Math.fround(this.position[stock].value - (trade.price * trade.quantity)).toFixed(2) * 1.00;
    }
    
    if (this.position[stock].quantity == 0) {
      delete this.position[stock];
      return;
    }
    
    if (updatePrice) {
      this.position[stock].price = Math.fround(this.position[stock].value / this.position[stock].quantity).toFixed(2) * 1.00;
    }
  }
}

module.exports = ParserBase;