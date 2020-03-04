<?php
/*
 * Created on Mar 4, 2020
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class Parser {

  const RECORD_TRADING_DATE     = 'trading-date';
  const RECORD_SWING_TRADE      = 'swing-trade';
  const RECORD_DAY_TRADE        = 'day-trade';
  const RECORD_FINANCIAL_DIGEST = 'financial-digest';
  
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
          break;
        case Parser::RECORD_DAY_TRADE:
          $this->readDayTrade($line);
          break;
        case Parser::RECORD_FINANCIAL_DIGEST:
          $this->readFinancialDigest($lineList, $key);
          break;
      }
    }
  }
  
  public function setTradingDate($tradingDate) {
    $this->tradingDate = $this->util->formatDate($tradingDate);
  }

  public function getTradingDate() {
    return $this->tradingDate;
  }
  
  public function addTrade($trade, $tradeType) {
    
    if (!isset($this->tradeList[$this->tradingDate])) {
      $this->tradeList[$this->tradingDate] = array(
        'swing' => array(),
        'day'   => array(),
        'fees'   => array(
          'settlementFee'   => 0,
          'registrationFee' => 0,
          'emoluments'      => 0,
          'operationalFee'  => 0,
          'executionFee'    => 0,
          'custodyFee'      => 0,
          'tax'             => 0,
          'incomeTax'       => 0,
          'otherFee'        => 0,
        )
      );
    }
    
    if ($tradeType == Parser::TRADE_TYPE_SWING_TRADE) {
      return;
    }
    
    $this->tradeList[$this->tradingDate][$tradeType][] = $trade;
    
    if ($tradeType == Parser::TRADE_TYPE_SWING_TRADE) {
      $this->updateStockPrice($trade['stock'], $trade['quantity'], $trade['price'], $trade['exchangeType']);
    }
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
  
  public function updateTradingFees($settlementFee, $registrationFee, $emoluments, $operationalFee, $executionFee, $custodyFee, $tax, $incomeTax, $otherFee) {

    $settlementFee   = $this->util->formatFloat($settlementFee);
    $registrationFee = $this->util->formatFloat($registrationFee);
    $emoluments      = $this->util->formatFloat($emoluments);
    $operationalFee  = $this->util->formatFloat($operationalFee);
    $executionFee    = $this->util->formatFloat($executionFee);
    $custodyFee      = $this->util->formatFloat($custodyFee);
    $tax             = $this->util->formatFloat($tax);
    $incomeTax       = $this->util->formatFloat($incomeTax);
    $otherFee        = $this->util->formatFloat($otherFee);
    
    $this->tradeList[$this->tradingDate]['fees']['settlementFee']   = bcadd($this->tradeList[$this->tradingDate]['fees']['settlementFee'], $settlementFee, 2);
    $this->tradeList[$this->tradingDate]['fees']['registrationFee'] = bcadd($this->tradeList[$this->tradingDate]['fees']['registrationFee'], $registrationFee, 2);
    $this->tradeList[$this->tradingDate]['fees']['emoluments']      = bcadd($this->tradeList[$this->tradingDate]['fees']['emoluments'], $emoluments, 2);
    $this->tradeList[$this->tradingDate]['fees']['operationalFee']  = bcadd($this->tradeList[$this->tradingDate]['fees']['operationalFee'], $operationalFee, 2);
    $this->tradeList[$this->tradingDate]['fees']['executionFee']    = bcadd($this->tradeList[$this->tradingDate]['fees']['executionFee'], $executionFee, 2);
    $this->tradeList[$this->tradingDate]['fees']['custodyFee']      = bcadd($this->tradeList[$this->tradingDate]['fees']['custodyFee'], $custodyFee, 2);
    $this->tradeList[$this->tradingDate]['fees']['tax']             = bcadd($this->tradeList[$this->tradingDate]['fees']['tax'], $tax, 2);
    $this->tradeList[$this->tradingDate]['fees']['incomeTax']       = bcadd($this->tradeList[$this->tradingDate]['fees']['incomeTax'], $incomeTax, 2);
    $this->tradeList[$this->tradingDate]['fees']['otherFee']        = bcadd($this->tradeList[$this->tradingDate]['fees']['otherFee'], $otherFee, 2);
  }
}
?>
