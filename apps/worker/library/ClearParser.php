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

    if (preg_match('/^(C|V) +(.*) +([0-9]{2}\/[0-9]{2}\/[0-9]{4}) +([0-9]+) +([0-9\-\.,]+) +(DAY TRADE) +([0-9\-\.,]+) +(C|D) +([0-9\-\.,]+)/', $line)) {
      return Parser::RECORD_DAY_TRADE;
    }

    if (preg_match('/^Resumo dos negócios.*Resumo Financeiro$/i', $line)) {
      return Parser::RECORD_FINANCIAL_DIGEST;
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

  public function readDayTrade($line) {
    
    preg_match('/^(C|V) +(.*) +([0-9]{2}\/[0-9]{2}\/[0-9]{4}) +([0-9]+) +([0-9\-\.,]+) +(DAY TRADE) +([0-9\-\.,]+) +(C|D) +([0-9\-\.,]+)/', $line, $matches);
    
    $exchangeType = preg_replace('/  +/', ' ', $matches[1]);
    $stock        = preg_replace('/  +/', ' ', $matches[2]);
    $dueDate      = preg_replace('/  +/', ' ', $matches[3]);
    $quantity     = preg_replace('/  +/', ' ', $matches[4]);
    $price        = preg_replace('/  +/', ' ', $matches[5]);
    $value        = preg_replace('/  +/', ' ', $matches[7]);
    $balanceType  = preg_replace('/  +/', ' ', $matches[8]);
    $brokerFee    = preg_replace('/  +/', ' ', $matches[9]);
    
    $dueDate   = $this->util->formatDate($dueDate);
    $price     = $this->util->formatFloat($price, 4);
    $value     = $this->util->formatFloat($value, 4);
    $brokerFee = $this->util->formatFloat($brokerFee);
    
    $trade = array(
      'exchangeType' => $exchangeType,
      'stock'        => $stock,
      'dueDate'      => $dueDate,
      'quantity'     => $quantity,
      'price'        => $price,
      'value'        => $value,
      'balanceType'  => $balanceType,
      'brokerFee'    => $brokerFee,
    );
    
    $this->addTrade($trade, Parser::TRADE_TYPE_DAY_TRADE);
  }

  public function readFinancialDigest($lineList, $key) {

    $settlementFee   = 0;
    $registrationFee = 0;
    $emoluments      = 0;
    $marketFee       = 0;
    $operationalFee  = 0;
    $executionFee    = 0;
    $custodyFee      = 0;
    $tax             = 0;
    $incomeTax       = 0;
    $otherFee        = 0;
        
    //Resumo dos Negócios                                                                                                           Resumo Financeiro
    //Debêntures                                                                                                             0,00   Clearing
    //Vendas à vista                                                                                                         0,00   Valor líquido das operações                                                         1.497,85          D
    //prexit($lineList[$key+3]);
    preg_match('/Compras à vista.*Taxa de liquidação\s*([0-9\-\.,]+) /i', $lineList[$key+3], $matchesSettlementFee);//Compras à vista                                                                                                    1.497,85   Taxa de liquidação                                                                      0,41          D
    preg_match('/Opções.*Taxa de Registro\s*([0-9\-\.,]+) /i', $lineList[$key+4], $matchesRegistrationFee);//Opções - compras                                                                                                       0,00   Taxa de Registro                                                                        0,00          D
    //Opções - vendas                                                                                                        0,00   Total CBLC                                                                          1.498,26          D
    //Operações à termo                                                                                                      0,00   Bolsa
    //Valor das oper. c/ títulos públ. (v. nom.)                                                                             0,00   Taxa de termo/opções                                                                      0,00        D
    //Valor das operações                                                                                                1.497,85   Taxa A.N.A.                                                                               0,00        D
    preg_match('/Emolumentos\s*([0-9\-\.,]+) /i', $lineList[$key+9], $matchesEmoluments);//                                                                                                                              Emolumentos                                                                               0,06        D
//    preg_match('/Total Bovespa( *\/ *Soma)?\s*([0-9\-\.,]+) /i', $lineList[$key+10], $matchesMarketFee);//                                                                                                                              Total Bovespa / Soma                                                                      0,06        D
    //Especificações diversas                                                                                                       Custos Operacionais
    //
    preg_match('/Taxa Operacional\s*([0-9\-\.,]+) /i', $lineList[$key+13], $matchesOperationalFee);//                                                                                                                              Taxa Operacional                                                                          0,00 D
    preg_match('/Execução\s*([0-9\-\.,]+)/i', $lineList[$key+14], $matchesExecutionFee);//                                                                                                                              Execução                                                                                  0,00
    preg_match('/.*Taxa de Custódia\s*([0-9\-\.,]+)/i', $lineList[$key+15], $matchesCustodyFee);//A coluna Q indica liquidação no Agente do Qualificado.                                                                        Taxa de Custódia                                                                          0,00
    preg_match('/Impostos\s*([0-9\-\.,]+)/i', $lineList[$key+16], $matchesTax);//                                                                                                                              Impostos                                                                                  0,00
    preg_match('/I\.R\.R\.F\. s\/ operações.* +([0-9\-\.,]+)/i', $lineList[$key+17], $matchesIncomeTax);//                                                                                                                              I.R.R.F. s/ operações, base R$0,00                                                        0,00
    preg_match('/Outros\s*([0-9\-\.,]+) /i', $lineList[$key+18], $matchesOtherFee);//                                                                                                                              Outros                                                                                    0,00 C

    $settlementFee   = $matchesSettlementFee ? $matchesSettlementFee[1] : 0;
    $registrationFee = $matchesRegistrationFee ? $matchesRegistrationFee[1] : 0;
    $emoluments      = $matchesEmoluments ? $matchesEmoluments[1] : 0;
    $operationalFee  = $matchesOperationalFee ? $matchesOperationalFee[1] : 0;
    $executionFee    = $matchesExecutionFee ? $matchesExecutionFee[1] : 0;
    $custodyFee      = $matchesCustodyFee ? $matchesCustodyFee[1] : 0;
    $tax             = $matchesTax ? $matchesTax[1] : 0;
    $incomeTax       = $matchesIncomeTax ? $matchesIncomeTax[1] : 0;
    $otherFee        = $matchesOtherFee ? $matchesOtherFee[1] : 0;
    
    $this->updateTradingFees($settlementFee, $registrationFee, $emoluments, $operationalFee, $executionFee, $custodyFee, $tax, $incomeTax, $otherFee);
  }
}
?>
