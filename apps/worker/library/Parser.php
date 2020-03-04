<?php
/*
 * Created on Mar 4, 2020
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class Parser {

  const RECORD_TRADING_DATE = 'trading-date';
  const RECORD_SWING_TRADE  = 'swing-date';
  
  const TRADE_TYPE_SWING_TRADE = 'swing';
  const TRADE_TYPE_DAY_TRADE   = 'day';

  private $tradingDate = null;  
  private $tradeList   = array();
  private $stockList   = array();
  
  public function __construct() {
    $this->util = MyTools::getDi()->get('util');
  }
  
  public function convertPdfToText($sourcePath) {
    
    $fileNamePdf = basename($sourcePath);
    $fileNameTxt = preg_replace('/.pdf$/i', '.txt', $fileNamePdf);
    $filePathTxt = $this->util->getFilePath('/temp/' . $fileNameTxt);
    
    $command = "/usr/local/bin/pdftotext \"$sourcePath\" \"$filePathTxt\" -layout -enc UTF-8";
    @exec($command, $result, $output);
    
    if ($result == 127 || !file_exists($filePathTxt)) {
      throw new MercatorException('Failure parsing file: ' . $sourcePath);
    }
    
    return $filePathTxt;
  }
  
  public function parse($filePath) {

    $lineList = file($filePath);
    
    for ($key=0; $key < count($lineList); $key++) {
      
      $line = trim($lineList[$key]);
      
      switch ($this->identifyRecord($line)) {
        case Parser::RECORD_TRADING_DATE:
          $this->setTradingDate($this->readTradingDate($lineList[$key + 1]));
          $key++;
          break;
        case Parser::RECORD_SWING_TRADE:
          $this->readSwingTrade($line);
//          echo "$line\n";
          break;
      }
    }
  }
  
  public function setTradingDate($tradingDate) {
    $this->tradingDate = $this->util->formatDate($tradingDate);
  }
  
  public function addTrade($trade, $tradeType) {
    
    if (!isset($this->tradeList[$this->tradingDate])) {
      $this->tradeList[$this->tradingDate] = array();
    }
    
    $this->tradeList[$this->tradingDate][] = $trade;
    
    $this->updateStockPrice($trade['stock'], $trade['quantity'], $trade['price'], $trade['exchangeType']);
  }
  
  private function updateStockPrice($stock, $quantity, $price, $exchangeType) {
    
    if (!isset($this->stockList[$stock])) {
      $this->stockList[$stock] = array('quantity' => 0, 'price' => 0, 'value' => 0);
    }
    
    $updatePrice = true;
    
    if ($exchangeType == 'C') {
      
      // Se estiver vendido e estiver comprando alguma de volta não vamos atualizar o preço médio
      if ($this->stockList[$stock]['quantity'] < 0) {
        $updatePrice = false;
      }
      
      $this->stockList[$stock]['quantity'] = bcadd($this->stockList[$stock]['quantity'], $quantity, 0);
      $this->stockList[$stock]['value']    = bcadd($this->stockList[$stock]['value'], ($price * $quantity), 2);
    } else {
      
      // Se estiver comprado e estiver vendendo alguma de volta não vamos atualizar o preço médio
      if ($this->stockList[$stock]['quantity'] > 0) {
        $updatePrice = false;
      }
       
      $this->stockList[$stock]['quantity'] = bcsub($this->stockList[$stock]['quantity'], $quantity, 0);
      $this->stockList[$stock]['value']    = bcsub($this->stockList[$stock]['value'], ($price * $quantity), 2);
    }
    
    if ($this->stockList[$stock]['quantity'] == 0) {
      unset($this->stockList[$stock]);
      return;
    }
    
    if ($updatePrice) {
      $this->stockList[$stock]['price'] = bcdiv($this->stockList[$stock]['value'], $this->stockList[$stock]['quantity'], 2);
    }

  }
  
  public function getTradeList() {
    return $this->tradeList;
  }
  
  public function getStockBalance() {
    
    ksort($this->stockList);
    return $this->stockList;
  }
}
?>
