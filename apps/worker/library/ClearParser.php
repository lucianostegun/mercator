<?php
/*
 * Created on Mar 4, 2020
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class ClearParser extends Parser {
  
  public function parseBatch($filePathList) {
    
    foreach ($filePathList as $filePath) {
      
      $filePathTxt = $this->convertPdfToText($filePath);
      
      $this->parse($filePathTxt);
    }
  }
  
  public function identifyRecord($line) {
    
    if (preg_match('/Data pregão$/', $line)) {
      return Parser::RECORD_TRADING_DATE;
    }
    
    if (preg_match('/^1-BOVESPA.*(C|D)$/', $line)) {
      return Parser::RECORD_SWING_TRADE;
    }
  }
  
  public function readTradingDate($line) {

    if (!preg_match('/([0-9]{2}\/[0-9]{2}\/[0-9]{4})/', $line, $matches)) {
      throw new MercatorException('Failure reading trading date: ' . $line);
    }
    
    return $matches[1];
  }
  
  public function readSwingTrade($line) {
    
    $market       = trim(preg_replace('/^(.{25}).*/', '\1', $line));
    $exchangeType = trim(preg_replace('/^.{25} *(C|V).*/', '\1', $line));
    $marketType   = trim(preg_replace('/^.{25} *(C|V) *([A-Z ]{20}) .*/', '\2', $line));
    $stock        = trim(preg_replace('/^.{78}(.{45}).*/', '\1', $line));
    $comments     = trim(preg_replace('/^.{81}.{45}(.*) ([\-0-9]+) * ([\-0-9\.\,]+) * ([\-0-9\.\,]+) *(C|D)$/', '\1', $line));
    $quantity     = trim(preg_replace('/.* ([\-0-9]+) * ([\-0-9\.\,]+) * ([\-0-9\.\,]+) *(C|D)$/', '\1', $line));
    $price        = trim(preg_replace('/.* ([\-0-9\.\,]+) * ([\-0-9\.\,]+) *(C|D)$/', '\1', $line));
    $value        = trim(preg_replace('/.* ([\-0-9\.\,]+) *(C|D)$/', '\1', $line));
    $balanceType  = trim(preg_replace('/.*(C|D)$/', '\1', $line));
    
    $market       = preg_replace('/  +/', ' ', $market);
    $exchangeType = preg_replace('/  +/', ' ', $exchangeType);
    $marketType   = preg_replace('/  +/', ' ', $marketType);
    $stock        = preg_replace('/  +/', ' ', $stock);
    $comments     = preg_replace('/  +/', ' ', $comments);
    $quantity     = preg_replace('/  +/', ' ', $quantity);
    $price        = preg_replace('/  +/', ' ', $price);
    $value        = preg_replace('/  +/', ' ', $value);
    $balanceType  = preg_replace('/  +/', ' ', $balanceType);
    
    $stock = preg_replace('/^(.*) (ON|PN|N2).*/', '\1 \2', $stock);
    $stock = preg_replace('/ (ED|EJ|EDJ) /', ' ', $stock);
    
    $price = $this->util->formatFloat($price);
    $value = $this->util->formatFloat($value);
    
//    if (!isset($tradeList[$tradingDay][$tradeType])) {
//      $tradeList[$tradingDay][$tradeType] = array();
//    }
    
//    if (!isset($stockBalanceList[$stock])) {
//      $stockBalanceList[$stock] = array('quantity' => 0, 'value' => 0);
//    }
    
    $trade = array(
      'market'       => $market,
      'exchangeType' => $exchangeType,
      'marketType'   => $marketType,
      'stock'        => $stock,
      'comments'     => $comments,
      'quantity'     => $quantity,
      'price'        => $price,
      'value'        => $value,
      'balanceType'  => $balanceType,
    );
    
    $this->addTrade($trade, Parser::TRADE_TYPE_SWING_TRADE);
  }
}
?>
