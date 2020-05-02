const Trade = function(type) {
  this.type         = type;
  this.exchangeType = null;
  this.stock        = null;
  this.dueDate      = null;
  this.quantity     = 0;
  this.price        = 0;
  this.value        = 0;
  this.balanceType  = null;
  this.brokerFee    = 0;
  this.balance      = 0;

  return this;
}

module.exports = Trade;