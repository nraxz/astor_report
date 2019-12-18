<?php

class order_details_rtf
{
   var $Db;
   var $Erro;
   var $Ini;
   var $Lookup;
   var $nm_data;
   var $Texto_tag;
   var $Arquivo;
   var $Tit_doc;
   var $sc_proc_grid; 
   var $NM_cmp_hidden = array();

   //---- 
   function __construct()
   {
      $this->nm_data   = new nm_data("en_us");
      $this->Texto_tag = "";
   }

   //---- 
   function monta_rtf()
   {
      $this->inicializa_vars();
      $this->gera_texto_tag();
      $this->grava_arquivo_rtf();
      if ($this->Ini->sc_export_ajax)
      {
          $this->Arr_result['file_export']  = NM_charset_to_utf8($this->Rtf_f);
          $this->Arr_result['title_export'] = NM_charset_to_utf8($this->Tit_doc);
          $Temp = ob_get_clean();
          if ($Temp !== false && trim($Temp) != "")
          {
              $this->Arr_result['htmOutput'] = NM_charset_to_utf8($Temp);
          }
          $oJson = new Services_JSON();
          echo $oJson->encode($this->Arr_result);
          exit;
      }
      else
      {
          $this->progress_bar_end();
      }
   }

   //----- 
   function inicializa_vars()
   {
      global $nm_lang;
      $dir_raiz          = strrpos($_SERVER['PHP_SELF'],"/") ;  
      $dir_raiz          = substr($_SERVER['PHP_SELF'], 0, $dir_raiz + 1) ;  
      $this->nm_location = $this->Ini->sc_protocolo . $this->Ini->server . $dir_raiz; 
      require_once($this->Ini->path_aplicacao . "order-details_total.class.php"); 
      $this->Tot      = new order_details_total($this->Ini->sc_page);
      $this->prep_modulos("Tot");
      $Gb_geral = "quebra_geral_" . $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['SC_Ind_Groupby'];
      if (method_exists($this->Tot,$Gb_geral))
      {
          $this->Tot->$Gb_geral();
          $this->count_ger = $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['tot_geral'][1];
      }
      if (!$this->Ini->sc_export_ajax) {
          require_once($this->Ini->path_lib_php . "/sc_progress_bar.php");
          $this->pb = new scProgressBar();
          $this->pb->setRoot($this->Ini->root);
          $this->pb->setDir($_SESSION['scriptcase']['order-details']['glo_nm_path_imag_temp'] . "/");
          $this->pb->setProgressbarMd5($_GET['pbmd5']);
          $this->pb->initialize();
          $this->pb->setReturnUrl("./");
          $this->pb->setReturnOption('volta_grid');
          $this->pb->setTotalSteps($this->count_ger);
      }
      $this->Arquivo    = "sc_rtf";
      $this->Arquivo   .= "_" . date("YmdHis") . "_" . rand(0, 1000);
      $this->Arquivo   .= "_order-details";
      $this->Arquivo   .= ".rtf";
      $this->Tit_doc    = "order-details.rtf";
   }
   //---- 
   function prep_modulos($modulo)
   {
      $this->$modulo->Ini    = $this->Ini;
      $this->$modulo->Db     = $this->Db;
      $this->$modulo->Erro   = $this->Erro;
      $this->$modulo->Lookup = $this->Lookup;
   }


   //----- 
   function gera_texto_tag()
   {
     global $nm_lang;
      global $nm_nada, $nm_lang;

      $_SESSION['scriptcase']['sc_sql_ult_conexao'] = ''; 
      $this->sc_proc_grid = false; 
      $nm_raiz_img  = ""; 
      if (isset($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['rtf_name']))
      {
          $this->Arquivo = $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['rtf_name'];
          $this->Tit_doc = $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['rtf_name'];
          unset($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['rtf_name']);
      }
      if (isset($_SESSION['scriptcase']['sc_apl_conf']['order-details']['field_display']) && !empty($_SESSION['scriptcase']['sc_apl_conf']['order-details']['field_display']))
      {
          foreach ($_SESSION['scriptcase']['sc_apl_conf']['order-details']['field_display'] as $NM_cada_field => $NM_cada_opc)
          {
              $this->NM_cmp_hidden[$NM_cada_field] = $NM_cada_opc;
          }
      }
      if (isset($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['usr_cmp_sel']) && !empty($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['usr_cmp_sel']))
      {
          foreach ($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['usr_cmp_sel'] as $NM_cada_field => $NM_cada_opc)
          {
              $this->NM_cmp_hidden[$NM_cada_field] = $NM_cada_opc;
          }
      }
      if (isset($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['php_cmp_sel']) && !empty($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['php_cmp_sel']))
      {
          foreach ($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['php_cmp_sel'] as $NM_cada_field => $NM_cada_opc)
          {
              $this->NM_cmp_hidden[$NM_cada_field] = $NM_cada_opc;
          }
      }
      $this->sc_where_orig   = $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['where_orig'];
      $this->sc_where_atual  = $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['where_pesq'];
      $this->sc_where_filtro = $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['where_pesq_filtro'];
      if (isset($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['campos_busca']) && !empty($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['campos_busca']))
      { 
          $Busca_temp = $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['campos_busca'];
          if ($_SESSION['scriptcase']['charset'] != "UTF-8")
          {
              $Busca_temp = NM_conv_charset($Busca_temp, $_SESSION['scriptcase']['charset'], "UTF-8");
          }
          $this->id = $Busca_temp['id']; 
          $tmp_pos = strpos($this->id, "##@@");
          if ($tmp_pos !== false && !is_array($this->id))
          {
              $this->id = substr($this->id, 0, $tmp_pos);
          }
          $this->uid = $Busca_temp['uid']; 
          $tmp_pos = strpos($this->uid, "##@@");
          if ($tmp_pos !== false && !is_array($this->uid))
          {
              $this->uid = substr($this->uid, 0, $tmp_pos);
          }
          $this->iditem = $Busca_temp['iditem']; 
          $tmp_pos = strpos($this->iditem, "##@@");
          if ($tmp_pos !== false && !is_array($this->iditem))
          {
              $this->iditem = substr($this->iditem, 0, $tmp_pos);
          }
          $this->persons = $Busca_temp['persons']; 
          $tmp_pos = strpos($this->persons, "##@@");
          if ($tmp_pos !== false && !is_array($this->persons))
          {
              $this->persons = substr($this->persons, 0, $tmp_pos);
          }
      } 
      $this->arr_export = array('label' => array(), 'lines' => array());
      $this->arr_span   = array();

      $this->Texto_tag .= "<table>\r\n";
      $this->Texto_tag .= "<tr>\r\n";
      foreach ($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['field_order'] as $Cada_col)
      { 
          $SC_Label = (isset($this->New_label['id'])) ? $this->New_label['id'] : "Order ID"; 
          if ($Cada_col == "id" && (!isset($this->NM_cmp_hidden[$Cada_col]) || $this->NM_cmp_hidden[$Cada_col] != "off"))
          {
              if (!NM_is_utf8($SC_Label))
              {
                  $SC_Label = sc_convert_encoding($SC_Label, "UTF-8", $_SESSION['scriptcase']['charset']);
              }
              $SC_Label = str_replace('<', '&lt;', $SC_Label);
              $SC_Label = str_replace('>', '&gt;', $SC_Label);
              $this->Texto_tag .= "<td>" . $SC_Label . "</td>\r\n";
          }
          $SC_Label = (isset($this->New_label['nominative'])) ? $this->New_label['nominative'] : "Nominative"; 
          if ($Cada_col == "nominative" && (!isset($this->NM_cmp_hidden[$Cada_col]) || $this->NM_cmp_hidden[$Cada_col] != "off"))
          {
              if (!NM_is_utf8($SC_Label))
              {
                  $SC_Label = sc_convert_encoding($SC_Label, "UTF-8", $_SESSION['scriptcase']['charset']);
              }
              $SC_Label = str_replace('<', '&lt;', $SC_Label);
              $SC_Label = str_replace('>', '&gt;', $SC_Label);
              $this->Texto_tag .= "<td>" . $SC_Label . "</td>\r\n";
          }
          $SC_Label = (isset($this->New_label['custdata'])) ? $this->New_label['custdata'] : "Custdata"; 
          if ($Cada_col == "custdata" && (!isset($this->NM_cmp_hidden[$Cada_col]) || $this->NM_cmp_hidden[$Cada_col] != "off"))
          {
              if (!NM_is_utf8($SC_Label))
              {
                  $SC_Label = sc_convert_encoding($SC_Label, "UTF-8", $_SESSION['scriptcase']['charset']);
              }
              $SC_Label = str_replace('<', '&lt;', $SC_Label);
              $SC_Label = str_replace('>', '&gt;', $SC_Label);
              $this->Texto_tag .= "<td>" . $SC_Label . "</td>\r\n";
          }
          $SC_Label = (isset($this->New_label['membdata'])) ? $this->New_label['membdata'] : "Membdata"; 
          if ($Cada_col == "membdata" && (!isset($this->NM_cmp_hidden[$Cada_col]) || $this->NM_cmp_hidden[$Cada_col] != "off"))
          {
              if (!NM_is_utf8($SC_Label))
              {
                  $SC_Label = sc_convert_encoding($SC_Label, "UTF-8", $_SESSION['scriptcase']['charset']);
              }
              $SC_Label = str_replace('<', '&lt;', $SC_Label);
              $SC_Label = str_replace('>', '&gt;', $SC_Label);
              $this->Texto_tag .= "<td>" . $SC_Label . "</td>\r\n";
          }
          $SC_Label = (isset($this->New_label['persons'])) ? $this->New_label['persons'] : "# of Tickets"; 
          if ($Cada_col == "persons" && (!isset($this->NM_cmp_hidden[$Cada_col]) || $this->NM_cmp_hidden[$Cada_col] != "off"))
          {
              if (!NM_is_utf8($SC_Label))
              {
                  $SC_Label = sc_convert_encoding($SC_Label, "UTF-8", $_SESSION['scriptcase']['charset']);
              }
              $SC_Label = str_replace('<', '&lt;', $SC_Label);
              $SC_Label = str_replace('>', '&gt;', $SC_Label);
              $this->Texto_tag .= "<td>" . $SC_Label . "</td>\r\n";
          }
          $SC_Label = (isset($this->New_label['tot'])) ? $this->New_label['tot'] : "Total Sales"; 
          if ($Cada_col == "tot" && (!isset($this->NM_cmp_hidden[$Cada_col]) || $this->NM_cmp_hidden[$Cada_col] != "off"))
          {
              if (!NM_is_utf8($SC_Label))
              {
                  $SC_Label = sc_convert_encoding($SC_Label, "UTF-8", $_SESSION['scriptcase']['charset']);
              }
              $SC_Label = str_replace('<', '&lt;', $SC_Label);
              $SC_Label = str_replace('>', '&gt;', $SC_Label);
              $this->Texto_tag .= "<td>" . $SC_Label . "</td>\r\n";
          }
          $SC_Label = (isset($this->New_label['discount'])) ? $this->New_label['discount'] : "Discount"; 
          if ($Cada_col == "discount" && (!isset($this->NM_cmp_hidden[$Cada_col]) || $this->NM_cmp_hidden[$Cada_col] != "off"))
          {
              if (!NM_is_utf8($SC_Label))
              {
                  $SC_Label = sc_convert_encoding($SC_Label, "UTF-8", $_SESSION['scriptcase']['charset']);
              }
              $SC_Label = str_replace('<', '&lt;', $SC_Label);
              $SC_Label = str_replace('>', '&gt;', $SC_Label);
              $this->Texto_tag .= "<td>" . $SC_Label . "</td>\r\n";
          }
          $SC_Label = (isset($this->New_label['status'])) ? $this->New_label['status'] : "Status"; 
          if ($Cada_col == "status" && (!isset($this->NM_cmp_hidden[$Cada_col]) || $this->NM_cmp_hidden[$Cada_col] != "off"))
          {
              if (!NM_is_utf8($SC_Label))
              {
                  $SC_Label = sc_convert_encoding($SC_Label, "UTF-8", $_SESSION['scriptcase']['charset']);
              }
              $SC_Label = str_replace('<', '&lt;', $SC_Label);
              $SC_Label = str_replace('>', '&gt;', $SC_Label);
              $this->Texto_tag .= "<td>" . $SC_Label . "</td>\r\n";
          }
          $SC_Label = (isset($this->New_label['strmultiprices'])) ? $this->New_label['strmultiprices'] : "Strmultiprices"; 
          if ($Cada_col == "strmultiprices" && (!isset($this->NM_cmp_hidden[$Cada_col]) || $this->NM_cmp_hidden[$Cada_col] != "off"))
          {
              if (!NM_is_utf8($SC_Label))
              {
                  $SC_Label = sc_convert_encoding($SC_Label, "UTF-8", $_SESSION['scriptcase']['charset']);
              }
              $SC_Label = str_replace('<', '&lt;', $SC_Label);
              $SC_Label = str_replace('>', '&gt;', $SC_Label);
              $this->Texto_tag .= "<td>" . $SC_Label . "</td>\r\n";
          }
          $SC_Label = (isset($this->New_label['ticket_detail'])) ? $this->New_label['ticket_detail'] : "Tickets"; 
          if ($Cada_col == "ticket_detail" && (!isset($this->NM_cmp_hidden[$Cada_col]) || $this->NM_cmp_hidden[$Cada_col] != "off"))
          {
              if (!NM_is_utf8($SC_Label))
              {
                  $SC_Label = sc_convert_encoding($SC_Label, "UTF-8", $_SESSION['scriptcase']['charset']);
              }
              $SC_Label = str_replace('<', '&lt;', $SC_Label);
              $SC_Label = str_replace('>', '&gt;', $SC_Label);
              $this->Texto_tag .= "<td>" . $SC_Label . "</td>\r\n";
          }
          $SC_Label = (isset($this->New_label['uid'])) ? $this->New_label['uid'] : "Uid"; 
          if ($Cada_col == "uid" && (!isset($this->NM_cmp_hidden[$Cada_col]) || $this->NM_cmp_hidden[$Cada_col] != "off"))
          {
              if (!NM_is_utf8($SC_Label))
              {
                  $SC_Label = sc_convert_encoding($SC_Label, "UTF-8", $_SESSION['scriptcase']['charset']);
              }
              $SC_Label = str_replace('<', '&lt;', $SC_Label);
              $SC_Label = str_replace('>', '&gt;', $SC_Label);
              $this->Texto_tag .= "<td>" . $SC_Label . "</td>\r\n";
          }
          $SC_Label = (isset($this->New_label['iditem'])) ? $this->New_label['iditem'] : "Iditem"; 
          if ($Cada_col == "iditem" && (!isset($this->NM_cmp_hidden[$Cada_col]) || $this->NM_cmp_hidden[$Cada_col] != "off"))
          {
              if (!NM_is_utf8($SC_Label))
              {
                  $SC_Label = sc_convert_encoding($SC_Label, "UTF-8", $_SESSION['scriptcase']['charset']);
              }
              $SC_Label = str_replace('<', '&lt;', $SC_Label);
              $SC_Label = str_replace('>', '&gt;', $SC_Label);
              $this->Texto_tag .= "<td>" . $SC_Label . "</td>\r\n";
          }
          $SC_Label = (isset($this->New_label['ts'])) ? $this->New_label['ts'] : "Ts"; 
          if ($Cada_col == "ts" && (!isset($this->NM_cmp_hidden[$Cada_col]) || $this->NM_cmp_hidden[$Cada_col] != "off"))
          {
              if (!NM_is_utf8($SC_Label))
              {
                  $SC_Label = sc_convert_encoding($SC_Label, "UTF-8", $_SESSION['scriptcase']['charset']);
              }
              $SC_Label = str_replace('<', '&lt;', $SC_Label);
              $SC_Label = str_replace('>', '&gt;', $SC_Label);
              $this->Texto_tag .= "<td>" . $SC_Label . "</td>\r\n";
          }
      } 
      $this->Texto_tag .= "</tr>\r\n";
      $this->nm_field_dinamico = array();
      $this->nm_order_dinamico = array();
      $nmgp_select_count = "SELECT count(*) AS countTest from " . $this->Ini->nm_tabela; 
      if (in_array(strtolower($this->Ini->nm_tpbanco), $this->Ini->nm_bases_sybase))
      { 
          $nmgp_select = "SELECT id, nominative, custdata, membdata, persons, tot, discount, status, strmultiprices, uid, iditem, ts, str_replace (convert(char(10),FROM_UNIXTIME(daytsattend),102), '.', '-') + ' ' + convert(char(8),FROM_UNIXTIME(daytsattend),20) as bookingday from " . $this->Ini->nm_tabela; 
      } 
      elseif (in_array(strtolower($this->Ini->nm_tpbanco), $this->Ini->nm_bases_mysql))
      { 
          $nmgp_select = "SELECT id, nominative, custdata, membdata, persons, tot, discount, status, strmultiprices, uid, iditem, ts, FROM_UNIXTIME(daytsattend) as bookingday from " . $this->Ini->nm_tabela; 
      } 
      else 
      { 
          $nmgp_select = "SELECT id, nominative, custdata, membdata, persons, tot, discount, status, strmultiprices, uid, iditem, ts, FROM_UNIXTIME(daytsattend) as bookingday from " . $this->Ini->nm_tabela; 
      } 
      $nmgp_select .= " " . $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['where_pesq'];
      $nmgp_select_count .= " " . $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['where_pesq'];
      if (isset($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['where_resumo']) && !empty($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['where_resumo'])) 
      { 
          if (empty($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['where_pesq'])) 
          { 
              $nmgp_select .= " where " . $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['where_resumo']; 
              $nmgp_select_count .= " where " . $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['where_resumo']; 
          } 
          else
          { 
              $nmgp_select .= " and (" . $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['where_resumo'] . ")"; 
              $nmgp_select_count .= " and (" . $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['where_resumo'] . ")"; 
          } 
      } 
      $nmgp_order_by = $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['order_grid'];
      $nmgp_select .= $nmgp_order_by; 
      $_SESSION['scriptcase']['sc_sql_ult_comando'] = $nmgp_select_count;
      $rt = $this->Db->Execute($nmgp_select_count);
      if ($rt === false && !$rt->EOF && $GLOBALS["NM_ERRO_IBASE"] != 1)
      {
         $this->Erro->mensagem(__FILE__, __LINE__, "banco", $this->Ini->Nm_lang['lang_errm_dber'], $this->Db->ErrorMsg());
         exit;
      }
      $this->count_ger = $rt->fields[0];
      $rt->Close();
      $_SESSION['scriptcase']['sc_sql_ult_comando'] = $nmgp_select;
      $rs = $this->Db->Execute($nmgp_select);
      if ($rs === false && !$rs->EOF && $GLOBALS["NM_ERRO_IBASE"] != 1)
      {
         $this->Erro->mensagem(__FILE__, __LINE__, "banco", $this->Ini->Nm_lang['lang_errm_dber'], $this->Db->ErrorMsg());
         exit;
      }
      $this->SC_seq_register = 0;
      $PB_tot = (isset($this->count_ger) && $this->count_ger > 0) ? "/" . $this->count_ger : "";
      while (!$rs->EOF)
      {
         $this->SC_seq_register++;
         if (!$this->Ini->sc_export_ajax) {
             $Mens_bar = $this->Ini->Nm_lang['lang_othr_prcs'];
             if ($_SESSION['scriptcase']['charset'] != "UTF-8") {
                 $Mens_bar = sc_convert_encoding($Mens_bar, "UTF-8", $_SESSION['scriptcase']['charset']);
             }
             $this->pb->setProgressbarMessage($Mens_bar . ": " . $this->SC_seq_register . $PB_tot);
             $this->pb->addSteps(1);
         }
         $this->Texto_tag .= "<tr>\r\n";
         $this->id = $rs->fields[0] ;  
         $this->id = (string)$this->id;
         $this->nominative = $rs->fields[1] ;  
         $this->custdata = $rs->fields[2] ;  
         $this->membdata = $rs->fields[3] ;  
         $this->persons = $rs->fields[4] ;  
         $this->persons = (string)$this->persons;
         $this->tot = $rs->fields[5] ;  
         $this->tot =  str_replace(",", ".", $this->tot);
         $this->tot = (string)$this->tot;
         $this->discount = $rs->fields[6] ;  
         $this->status = $rs->fields[7] ;  
         $this->strmultiprices = $rs->fields[8] ;  
         $this->uid = $rs->fields[9] ;  
         $this->uid = (string)$this->uid;
         $this->iditem = $rs->fields[10] ;  
         $this->iditem = (string)$this->iditem;
         $this->ts = $rs->fields[11] ;  
         $this->ts = (string)$this->ts;
         $this->bookingday = $rs->fields[12] ;  
         $this->sc_proc_grid = true; 
         $_SESSION['scriptcase']['order-details']['contr_erro'] = 'on';
  $json = $this->membdata ;
$json_decoded = json_decode($json,TRUE);
$table = "<table>";
foreach($json_decoded as $result){
    $table .="<tr><td>". $result['Full Name']."</td></tr>"; 
}
$table .="</table>";

$this->membdata  = $table;


$emails=  $this->extract_emails_from($this->custdata );
$this->custdata  = $emails[0];
$tickets = explode(";",$this->strmultiprices );

$this->strmultiprices  = $this->ticketsDetails($tickets);
$_SESSION['scriptcase']['order-details']['contr_erro'] = 'off'; 
         foreach ($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['field_order'] as $Cada_col)
         { 
            if (!isset($this->NM_cmp_hidden[$Cada_col]) || $this->NM_cmp_hidden[$Cada_col] != "off")
            { 
                $NM_func_exp = "NM_export_" . $Cada_col;
                $this->$NM_func_exp();
            } 
         } 
         $this->Texto_tag .= "</tr>\r\n";
         $rs->MoveNext();
      }
      $this->Texto_tag .= "</table>\r\n";
      if(isset($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['export_sel_columns']['field_order']))
      {
          $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['field_order'] = $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['export_sel_columns']['field_order'];
          unset($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['export_sel_columns']['field_order']);
      }
      if(isset($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['export_sel_columns']['usr_cmp_sel']))
      {
          $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['usr_cmp_sel'] = $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['export_sel_columns']['usr_cmp_sel'];
          unset($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['export_sel_columns']['usr_cmp_sel']);
      }
      $rs->Close();
   }
   //----- id
   function NM_export_id()
   {
         nmgp_Form_Num_Val($this->id, $_SESSION['scriptcase']['reg_conf']['grup_num'], $_SESSION['scriptcase']['reg_conf']['dec_num'], "0", "S", "2", "", "N:" . $_SESSION['scriptcase']['reg_conf']['neg_num'] , $_SESSION['scriptcase']['reg_conf']['simb_neg'], $_SESSION['scriptcase']['reg_conf']['num_group_digit']) ; 
         if (!NM_is_utf8($this->id))
         {
             $this->id = sc_convert_encoding($this->id, "UTF-8", $_SESSION['scriptcase']['charset']);
         }
         $this->id = str_replace('<', '&lt;', $this->id);
         $this->id = str_replace('>', '&gt;', $this->id);
         $this->Texto_tag .= "<td>" . $this->id . "</td>\r\n";
   }
   //----- nominative
   function NM_export_nominative()
   {
         $this->nominative = html_entity_decode($this->nominative, ENT_COMPAT, $_SESSION['scriptcase']['charset']);
         $this->nominative = strip_tags($this->nominative);
         if (!NM_is_utf8($this->nominative))
         {
             $this->nominative = sc_convert_encoding($this->nominative, "UTF-8", $_SESSION['scriptcase']['charset']);
         }
         $this->nominative = str_replace('<', '&lt;', $this->nominative);
         $this->nominative = str_replace('>', '&gt;', $this->nominative);
         $this->Texto_tag .= "<td>" . $this->nominative . "</td>\r\n";
   }
   //----- custdata
   function NM_export_custdata()
   {
         $this->custdata = html_entity_decode($this->custdata, ENT_COMPAT, $_SESSION['scriptcase']['charset']);
         $this->custdata = strip_tags($this->custdata);
         if (!NM_is_utf8($this->custdata))
         {
             $this->custdata = sc_convert_encoding($this->custdata, "UTF-8", $_SESSION['scriptcase']['charset']);
         }
         $this->custdata = str_replace('<', '&lt;', $this->custdata);
         $this->custdata = str_replace('>', '&gt;', $this->custdata);
         $this->Texto_tag .= "<td>" . $this->custdata . "</td>\r\n";
   }
   //----- membdata
   function NM_export_membdata()
   {
         $this->membdata = html_entity_decode($this->membdata, ENT_COMPAT, $_SESSION['scriptcase']['charset']);
         $this->membdata = strip_tags($this->membdata);
         if (!NM_is_utf8($this->membdata))
         {
             $this->membdata = sc_convert_encoding($this->membdata, "UTF-8", $_SESSION['scriptcase']['charset']);
         }
         $this->membdata = str_replace('<', '&lt;', $this->membdata);
         $this->membdata = str_replace('>', '&gt;', $this->membdata);
         $this->Texto_tag .= "<td>" . $this->membdata . "</td>\r\n";
   }
   //----- persons
   function NM_export_persons()
   {
         nmgp_Form_Num_Val($this->persons, $_SESSION['scriptcase']['reg_conf']['grup_num'], $_SESSION['scriptcase']['reg_conf']['dec_num'], "0", "S", "2", "", "N:" . $_SESSION['scriptcase']['reg_conf']['neg_num'] , $_SESSION['scriptcase']['reg_conf']['simb_neg'], $_SESSION['scriptcase']['reg_conf']['num_group_digit']) ; 
         if (!NM_is_utf8($this->persons))
         {
             $this->persons = sc_convert_encoding($this->persons, "UTF-8", $_SESSION['scriptcase']['charset']);
         }
         $this->persons = str_replace('<', '&lt;', $this->persons);
         $this->persons = str_replace('>', '&gt;', $this->persons);
         $this->Texto_tag .= "<td>" . $this->persons . "</td>\r\n";
   }
   //----- tot
   function NM_export_tot()
   {
         nmgp_Form_Num_Val($this->tot, $_SESSION['scriptcase']['reg_conf']['grup_val'], $_SESSION['scriptcase']['reg_conf']['dec_val'], "2", "S", "2", "", "V:" . $_SESSION['scriptcase']['reg_conf']['monet_f_pos'] . ":" . $_SESSION['scriptcase']['reg_conf']['monet_f_neg'], $_SESSION['scriptcase']['reg_conf']['simb_neg'], $_SESSION['scriptcase']['reg_conf']['unid_mont_group_digit']) ; 
         if (!NM_is_utf8($this->tot))
         {
             $this->tot = sc_convert_encoding($this->tot, "UTF-8", $_SESSION['scriptcase']['charset']);
         }
         $this->tot = str_replace('<', '&lt;', $this->tot);
         $this->tot = str_replace('>', '&gt;', $this->tot);
         $this->Texto_tag .= "<td>" . $this->tot . "</td>\r\n";
   }
   //----- discount
   function NM_export_discount()
   {
         $this->discount = html_entity_decode($this->discount, ENT_COMPAT, $_SESSION['scriptcase']['charset']);
         $this->discount = strip_tags($this->discount);
         if (!NM_is_utf8($this->discount))
         {
             $this->discount = sc_convert_encoding($this->discount, "UTF-8", $_SESSION['scriptcase']['charset']);
         }
         $this->discount = str_replace('<', '&lt;', $this->discount);
         $this->discount = str_replace('>', '&gt;', $this->discount);
         $this->Texto_tag .= "<td>" . $this->discount . "</td>\r\n";
   }
   //----- status
   function NM_export_status()
   {
         if ($this->status !== "&nbsp;") 
         { 
             $this->status = sc_strtolower($this->status); 
             $this->status = ucfirst($this->status); 
         } 
         $this->status = html_entity_decode($this->status, ENT_COMPAT, $_SESSION['scriptcase']['charset']);
         $this->status = strip_tags($this->status);
         if (!NM_is_utf8($this->status))
         {
             $this->status = sc_convert_encoding($this->status, "UTF-8", $_SESSION['scriptcase']['charset']);
         }
         $this->status = str_replace('<', '&lt;', $this->status);
         $this->status = str_replace('>', '&gt;', $this->status);
         $this->Texto_tag .= "<td>" . $this->status . "</td>\r\n";
   }
   //----- strmultiprices
   function NM_export_strmultiprices()
   {
         $this->strmultiprices = html_entity_decode($this->strmultiprices, ENT_COMPAT, $_SESSION['scriptcase']['charset']);
         $this->strmultiprices = strip_tags($this->strmultiprices);
         if (!NM_is_utf8($this->strmultiprices))
         {
             $this->strmultiprices = sc_convert_encoding($this->strmultiprices, "UTF-8", $_SESSION['scriptcase']['charset']);
         }
         $this->strmultiprices = str_replace('<', '&lt;', $this->strmultiprices);
         $this->strmultiprices = str_replace('>', '&gt;', $this->strmultiprices);
         $this->Texto_tag .= "<td>" . $this->strmultiprices . "</td>\r\n";
   }
   //----- ticket_detail
   function NM_export_ticket_detail()
   {
         if (!NM_is_utf8($this->ticket_detail))
         {
             $this->ticket_detail = sc_convert_encoding($this->ticket_detail, "UTF-8", $_SESSION['scriptcase']['charset']);
         }
         $this->ticket_detail = str_replace('<', '&lt;', $this->ticket_detail);
         $this->ticket_detail = str_replace('>', '&gt;', $this->ticket_detail);
         $this->Texto_tag .= "<td>" . $this->ticket_detail . "</td>\r\n";
   }
   //----- uid
   function NM_export_uid()
   {
         nmgp_Form_Num_Val($this->uid, $_SESSION['scriptcase']['reg_conf']['grup_num'], $_SESSION['scriptcase']['reg_conf']['dec_num'], "0", "S", "2", "", "N:" . $_SESSION['scriptcase']['reg_conf']['neg_num'] , $_SESSION['scriptcase']['reg_conf']['simb_neg'], $_SESSION['scriptcase']['reg_conf']['num_group_digit']) ; 
         if (!NM_is_utf8($this->uid))
         {
             $this->uid = sc_convert_encoding($this->uid, "UTF-8", $_SESSION['scriptcase']['charset']);
         }
         $this->uid = str_replace('<', '&lt;', $this->uid);
         $this->uid = str_replace('>', '&gt;', $this->uid);
         $this->Texto_tag .= "<td>" . $this->uid . "</td>\r\n";
   }
   //----- iditem
   function NM_export_iditem()
   {
         nmgp_Form_Num_Val($this->iditem, $_SESSION['scriptcase']['reg_conf']['grup_num'], $_SESSION['scriptcase']['reg_conf']['dec_num'], "0", "S", "2", "", "N:" . $_SESSION['scriptcase']['reg_conf']['neg_num'] , $_SESSION['scriptcase']['reg_conf']['simb_neg'], $_SESSION['scriptcase']['reg_conf']['num_group_digit']) ; 
         if (!NM_is_utf8($this->iditem))
         {
             $this->iditem = sc_convert_encoding($this->iditem, "UTF-8", $_SESSION['scriptcase']['charset']);
         }
         $this->iditem = str_replace('<', '&lt;', $this->iditem);
         $this->iditem = str_replace('>', '&gt;', $this->iditem);
         $this->Texto_tag .= "<td>" . $this->iditem . "</td>\r\n";
   }
   //----- ts
   function NM_export_ts()
   {
         nmgp_Form_Num_Val($this->ts, $_SESSION['scriptcase']['reg_conf']['grup_num'], $_SESSION['scriptcase']['reg_conf']['dec_num'], "0", "S", "2", "", "N:" . $_SESSION['scriptcase']['reg_conf']['neg_num'] , $_SESSION['scriptcase']['reg_conf']['simb_neg'], $_SESSION['scriptcase']['reg_conf']['num_group_digit']) ; 
         if (!NM_is_utf8($this->ts))
         {
             $this->ts = sc_convert_encoding($this->ts, "UTF-8", $_SESSION['scriptcase']['charset']);
         }
         $this->ts = str_replace('<', '&lt;', $this->ts);
         $this->ts = str_replace('>', '&gt;', $this->ts);
         $this->Texto_tag .= "<td>" . $this->ts . "</td>\r\n";
   }

   //----- 
   function grava_arquivo_rtf()
   {
      global $nm_lang, $doc_wrap;
      $this->Rtf_f = $this->Ini->root . $this->Ini->path_imag_temp . "/" . $this->Arquivo;
      $rtf_f       = fopen($this->Ini->root . $this->Ini->path_imag_temp . "/" . $this->Arquivo, "w");
      require_once($this->Ini->path_third      . "/rtf_new/document_generator/cl_xml2driver.php"); 
      $text_ok  =  "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n"; 
      $text_ok .=  "<DOC config_file=\"" . $this->Ini->path_third . "/rtf_new/doc_config.inc\" >\r\n"; 
      $text_ok .=  $this->Texto_tag; 
      $text_ok .=  "</DOC>\r\n"; 
      $xml = new nDOCGEN($text_ok,"RTF"); 
      fwrite($rtf_f, $xml->get_result_file());
      fclose($rtf_f);
   }

   function nm_conv_data_db($dt_in, $form_in, $form_out)
   {
       $dt_out = $dt_in;
       if (strtoupper($form_in) == "DB_FORMAT")
       {
           if ($dt_out == "null" || $dt_out == "")
           {
               $dt_out = "";
               return $dt_out;
           }
           $form_in = "AAAA-MM-DD";
       }
       if (strtoupper($form_out) == "DB_FORMAT")
       {
           if (empty($dt_out))
           {
               $dt_out = "null";
               return $dt_out;
           }
           $form_out = "AAAA-MM-DD";
       }
       nm_conv_form_data($dt_out, $form_in, $form_out);
       return $dt_out;
   }
   function progress_bar_end()
   {
      unset($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['rtf_file']);
      if (is_file($this->Ini->root . $this->Ini->path_imag_temp . "/" . $this->Arquivo))
      {
          $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['rtf_file'] = $this->Ini->root . $this->Ini->path_imag_temp . "/" . $this->Arquivo;
      }
      $path_doc_md5 = md5($this->Ini->path_imag_temp . "/" . $this->Arquivo);
      $_SESSION['sc_session'][$this->Ini->sc_page]['order-details'][$path_doc_md5][0] = $this->Ini->path_imag_temp . "/" . $this->Arquivo;
      $_SESSION['sc_session'][$this->Ini->sc_page]['order-details'][$path_doc_md5][1] = $this->Tit_doc;
      $Mens_bar = $this->Ini->Nm_lang['lang_othr_file_msge'];
      if ($_SESSION['scriptcase']['charset'] != "UTF-8") {
          $Mens_bar = sc_convert_encoding($Mens_bar, "UTF-8", $_SESSION['scriptcase']['charset']);
      }
      $this->pb->setProgressbarMessage($Mens_bar);
      $this->pb->setDownloadLink($this->Ini->path_imag_temp . "/" . $this->Arquivo);
      $this->pb->setDownloadMd5($path_doc_md5);
      $this->pb->completed();
   }
   //---- 
   function monta_html()
   {
      global $nm_url_saida, $nm_lang;
      include($this->Ini->path_btn . $this->Ini->Str_btn_grid);
      unset($_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['rtf_file']);
      if (is_file($this->Ini->root . $this->Ini->path_imag_temp . "/" . $this->Arquivo))
      {
          $_SESSION['sc_session'][$this->Ini->sc_page]['order-details']['rtf_file'] = $this->Ini->root . $this->Ini->path_imag_temp . "/" . $this->Arquivo;
      }
      $path_doc_md5 = md5($this->Ini->path_imag_temp . "/" . $this->Arquivo);
      $_SESSION['sc_session'][$this->Ini->sc_page]['order-details'][$path_doc_md5][0] = $this->Ini->path_imag_temp . "/" . $this->Arquivo;
      $_SESSION['sc_session'][$this->Ini->sc_page]['order-details'][$path_doc_md5][1] = $this->Tit_doc;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
            "http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd">
<HTML<?php echo $_SESSION['scriptcase']['reg_conf']['html_dir'] ?>>
<HEAD>
 <TITLE><?php echo $this->Ini->Nm_lang['lang_othr_grid_title'] ?> j3x_vikevents_orders :: RTF</TITLE>
 <META http-equiv="Content-Type" content="text/html; charset=<?php echo $_SESSION['scriptcase']['charset_html'] ?>" />
<?php
if ($_SESSION['scriptcase']['proc_mobile'])
{
?>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
<?php
}
?>
  <META http-equiv="Expires" content="Fri, Jan 01 1900 00:00:00 GMT"/>
  <META http-equiv="Last-Modified" content="<?php echo gmdate("D, d M Y H:i:s"); ?> GMT"/>
  <META http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate"/>
  <META http-equiv="Cache-Control" content="post-check=0, pre-check=0"/>
  <META http-equiv="Pragma" content="no-cache"/>
 <link rel="shortcut icon" href="../_lib/img/scriptcase__NM__ico__NM__favicon.ico">
  <link rel="stylesheet" type="text/css" href="../_lib/css/<?php echo $this->Ini->str_schema_all ?>_export.css" /> 
  <link rel="stylesheet" type="text/css" href="../_lib/css/<?php echo $this->Ini->str_schema_all ?>_export<?php echo $_SESSION['scriptcase']['reg_conf']['css_dir'] ?>.css" /> 
 <?php
 if(isset($this->Ini->str_google_fonts) && !empty($this->Ini->str_google_fonts))
 {
 ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $this->Ini->str_google_fonts ?>" />
 <?php
 }
 ?>
  <link rel="stylesheet" type="text/css" href="../_lib/buttons/<?php echo $this->Ini->Str_btn_css ?>" /> 
</HEAD>
<BODY class="scExportPage">
<?php echo $this->Ini->Ajax_result_set ?>
<table style="border-collapse: collapse; border-width: 0; height: 100%; width: 100%"><tr><td style="padding: 0; text-align: center; vertical-align: middle">
 <table class="scExportTable" align="center">
  <tr>
   <td class="scExportTitle" style="height: 25px">RTF</td>
  </tr>
  <tr>
   <td class="scExportLine" style="width: 100%">
    <table style="border-collapse: collapse; border-width: 0; width: 100%"><tr><td class="scExportLineFont" style="padding: 3px 0 0 0" id="idMessage">
    <?php echo $this->Ini->Nm_lang['lang_othr_file_msge'] ?>
    </td><td class="scExportLineFont" style="text-align:right; padding: 3px 0 0 0">
     <?php echo nmButtonOutput($this->arr_buttons, "bexportview", "document.Fview.submit()", "document.Fview.submit()", "idBtnView", "", "", "", "", "", "", $this->Ini->path_botoes, "", "", "", "", "", "only_text", "text_right", "", "", "", "", "", "", "");
 ?>
     <?php echo nmButtonOutput($this->arr_buttons, "bdownload", "document.Fdown.submit()", "document.Fdown.submit()", "idBtnDown", "", "", "", "", "", "", $this->Ini->path_botoes, "", "", "", "", "", "only_text", "text_right", "", "", "", "", "", "", "");
 ?>
     <?php echo nmButtonOutput($this->arr_buttons, "bvoltar", "document.F0.submit()", "document.F0.submit()", "idBtnBack", "", "", "", "", "", "", $this->Ini->path_botoes, "", "", "", "", "", "only_text", "text_right", "", "", "", "", "", "", "");
 ?>
    </td></tr></table>
   </td>
  </tr>
 </table>
</td></tr></table>
<form name="Fview" method="get" action="<?php echo $this->Ini->path_imag_temp . "/" . $this->Arquivo ?>" target="_blank" style="display: none"> 
</form>
<form name="Fdown" method="get" action="order-details_download.php" target="_blank" style="display: none"> 
<input type="hidden" name="script_case_init" value="<?php echo NM_encode_input($this->Ini->sc_page); ?>"> 
<input type="hidden" name="nm_tit_doc" value="order-details"> 
<input type="hidden" name="nm_name_doc" value="<?php echo $path_doc_md5 ?>"> 
</form>
<FORM name="F0" method=post action="./"> 
<INPUT type="hidden" name="script_case_init" value="<?php echo NM_encode_input($this->Ini->sc_page); ?>"> 
<INPUT type="hidden" name="script_case_session" value="<?php echo NM_encode_input(session_id()); ?>"> 
<INPUT type="hidden" name="nmgp_opcao" value="volta_grid"> 
</FORM> 
</BODY>
</HTML>
<?php
   }
   function nm_gera_mask(&$nm_campo, $nm_mask)
   { 
      $trab_campo = $nm_campo;
      $trab_mask  = $nm_mask;
      $tam_campo  = strlen($nm_campo);
      $trab_saida = "";
      $mask_num = false;
      for ($x=0; $x < strlen($trab_mask); $x++)
      {
          if (substr($trab_mask, $x, 1) == "#")
          {
              $mask_num = true;
              break;
          }
      }
      if ($mask_num )
      {
          $ver_duas = explode(";", $trab_mask);
          if (isset($ver_duas[1]) && !empty($ver_duas[1]))
          {
              $cont1 = count(explode("#", $ver_duas[0])) - 1;
              $cont2 = count(explode("#", $ver_duas[1])) - 1;
              if ($cont2 >= $tam_campo)
              {
                  $trab_mask = $ver_duas[1];
              }
              else
              {
                  $trab_mask = $ver_duas[0];
              }
          }
          $tam_mask = strlen($trab_mask);
          $xdados = 0;
          for ($x=0; $x < $tam_mask; $x++)
          {
              if (substr($trab_mask, $x, 1) == "#" && $xdados < $tam_campo)
              {
                  $trab_saida .= substr($trab_campo, $xdados, 1);
                  $xdados++;
              }
              elseif ($xdados < $tam_campo)
              {
                  $trab_saida .= substr($trab_mask, $x, 1);
              }
          }
          if ($xdados < $tam_campo)
          {
              $trab_saida .= substr($trab_campo, $xdados);
          }
          $nm_campo = $trab_saida;
          return;
      }
      for ($ix = strlen($trab_mask); $ix > 0; $ix--)
      {
           $char_mask = substr($trab_mask, $ix - 1, 1);
           if ($char_mask != "x" && $char_mask != "z")
           {
               $trab_saida = $char_mask . $trab_saida;
           }
           else
           {
               if ($tam_campo != 0)
               {
                   $trab_saida = substr($trab_campo, $tam_campo - 1, 1) . $trab_saida;
                   $tam_campo--;
               }
               else
               {
                   $trab_saida = "0" . $trab_saida;
               }
           }
      }
      if ($tam_campo != 0)
      {
          $trab_saida = substr($trab_campo, 0, $tam_campo) . $trab_saida;
          $trab_mask  = str_repeat("z", $tam_campo) . $trab_mask;
      }
   
      $iz = 0; 
      for ($ix = 0; $ix < strlen($trab_mask); $ix++)
      {
           $char_mask = substr($trab_mask, $ix, 1);
           if ($char_mask != "x" && $char_mask != "z")
           {
               if ($char_mask == "." || $char_mask == ",")
               {
                   $trab_saida = substr($trab_saida, 0, $iz) . substr($trab_saida, $iz + 1);
               }
               else
               {
                   $iz++;
               }
           }
           elseif ($char_mask == "x" || substr($trab_saida, $iz, 1) != "0")
           {
               $ix = strlen($trab_mask) + 1;
           }
           else
           {
               $trab_saida = substr($trab_saida, 0, $iz) . substr($trab_saida, $iz + 1);
           }
      }
      $nm_campo = $trab_saida;
   } 
function extract_emails_from($string)
{
$_SESSION['scriptcase']['order-details']['contr_erro'] = 'on';
  
 preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $string, $matches);
         return $matches[0];

$_SESSION['scriptcase']['order-details']['contr_erro'] = 'off';
}
function ticketname($tick_id)
{
$_SESSION['scriptcase']['order-details']['contr_erro'] = 'on';
  

$check_sql = "SELECT Concat(name, ' ', descr)"
   . " FROM j3x_vikevents_multiprices"
   . " WHERE id = '" . $tick_id . "'";
 
      $nm_select = $check_sql; 
      $_SESSION['scriptcase']['sc_sql_ult_comando'] = $nm_select; 
      $_SESSION['scriptcase']['sc_sql_ult_conexao'] = ''; 
      $this->rs = array();
      if ($rx = $this->Db->Execute($nm_select)) 
      { 
          $y = 0; 
          $nm_count = $rx->FieldCount();
          while (!$rx->EOF)
          { 
                 for ($x = 0; $x < $nm_count; $x++)
                 { 
                        $this->rs[$y] [$x] = $rx->fields[$x];
                 }
                 $y++; 
                 $rx->MoveNext();
          } 
          $rx->Close();
      } 
      elseif (isset($GLOBALS["NM_ERRO_IBASE"]) && $GLOBALS["NM_ERRO_IBASE"] != 1)  
      { 
          $this->rs = false;
          $this->rs_erro = $this->Db->ErrorMsg();
      } 
;

if (isset($this->rs[0][0]))     
{
    return $name = $this->rs[0][0];
   
}
$_SESSION['scriptcase']['order-details']['contr_erro'] = 'off';
}
function ticketsDetails($tickets)
{
$_SESSION['scriptcase']['order-details']['contr_erro'] = 'on';
  
$a = array();
foreach($tickets as $ticket){
	$disc = explode("=", $ticket);
	array_push($a, $this->ticketname($disc[1]));
	}
$bs = array_count_values($a);
$html='';
foreach($bs as $k=>$v){
	$html .= $k. ' '. $v.'<br>';
	}

return $html;

$_SESSION['scriptcase']['order-details']['contr_erro'] = 'off';
}
}

?>
