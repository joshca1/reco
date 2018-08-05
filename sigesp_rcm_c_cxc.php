<?php
class sigesp_rcm_c_cxc
{
	//-----------------------------------------------------------------------------------------------------------------------------------
	function sigesp_rcm_c_cxc()
	{	
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: sigesp_rcm_c_cxc
		//		   Access: public 
		//	  Description: Constructor de la Clase
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		require_once("../shared/class_folder/sigesp_include.php");
		$io_include=new sigesp_include();
		$io_conexion=$io_include->uf_conectar();
		require_once("../shared/class_folder/class_sql.php");
		$this->io_sql_origen=new class_sql($io_conexion);	
		$this->io_sql=new class_sql($io_conexion);	
		require_once("../shared/class_folder/class_mensajes.php");
		$this->io_mensajes=new class_mensajes();		
		require_once("../shared/class_folder/class_funciones.php");
		$this->io_funciones=new class_funciones();
		require_once("../shared/class_folder/sigesp_c_reconvertir_monedabsf.php");
		$this->io_rcbsf= new sigesp_c_reconvertir_monedabsf();
		require_once("../shared/class_folder/sigesp_c_seguridad.php");
		$this->seguridad=   new sigesp_c_seguridad();
		$this->ls_codemp=$_SESSION["la_empresa"]["codemp"];
/*		$ld_fecha=date("Y_m_d_H_i");
		$ls_nombrearchivo="resultado/resultado_export".$ld_fecha.".txt";
		$this->lo_archivo=@fopen("$ls_nombrearchivo","a+");*/
	}// end function sigesp_rcm_c_cxc
	//-----------------------------------------------------------------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------------------------------------------------------
	function uf_destructor()
	{	
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_destructor
		//		   Access: public 
		//	  Description: Destructor de la Clase
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		unset($this->io_mensajes);		
		unset($this->io_funciones);		
	}// end function uf_destructor
	//-----------------------------------------------------------------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------------------------------------------------------
	function uf_convertir_data($aa_seguridad)
	{	
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_data
		//		   Access: public
		//     Argumentos: $aa_seguridad  //Arreglo de Seguridad
		//	   Creado Por: santi consultores
		//    Description: Funcion que se encarga de hacer el llamado a cada una de las sub-funciones que hacen la reconversion de
		//                 las tablas del modulo de inventario. 
		// Fecha Creación: 13/05/2018 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		/*****************************************************************************************************************
		****************************************************************************************************************

		revisar la tabla cxc_documento
		y cxc_dt_documento
		cxc_dt_movdoc
		 en otras instituciones y si tiene data incluirla en este php por que las que tengo no tienen data, por eso 
		 no las incluyo

		****************************************************************************************************************
		*******************************************************************************************************************/
		$this->io_sql_origen->begin_transaction();
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxcanticipos();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxc_cmp_ret();
		}
		if($lb_valido) 
		{	
			$lb_valido=$this->uf_convertir_cxc_cotiza_pedidos();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxc_detalle();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxc_dt_anticipos();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxc_dt_cargos();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxc_dt_cargos_cotped();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxc_dt_cotiza_pedidos();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxc_dt_movcobro();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxc_dt_scg();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxc_dt_spg();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxc_dt_spi();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxc_factura();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxc_ingresos();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxc_movbaco_asoc();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->uf_convertir_cxc_movimientos();
		}
		if($lb_valido)
		{	
			$lb_valido=$this->io_rcbsf->uf_insert_check_scv('CXC',$aa_seguridad);
		}
		if($lb_valido)
		{
			$this->io_sql_origen->commit();
		}
		else
		{
			$this->io_sql_origen->rollback();
		}
		return $lb_valido;
	}
	//-----------------------------------------------------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------------------------------------------------
	function uf_convertir_cxcanticipos()
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxcanticipos
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_anticipos e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT codemp, idant,id_cliente, monant".
				"  FROM cxc_anticipos".
				" WHERE codemp='".$this->ls_codemp."'";
		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxcanticipos ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				$ls_codemp= $row["codemp"]; 
				$ls_idant= $row["idant"];
				$li_monant= $row["monant"];
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","monant");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_monant);
	
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codemp");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codemp);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","idant");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_idant);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_anticipos",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxcanticipos
	//-----------------------------------------------------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------------------------------------------------
	function uf_convertir_cxc_cmp_ret()
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxc_cmp_ret
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_cmp_ret e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT codemp, tipdoc, id_doc, numretdoc,".
				" totcmp_sin_iva, totcmp_con_iva, basimp, monimp, monret ".
				"  FROM cxc_cmp_ret".
				" WHERE codemp='".$this->ls_codemp."'";
		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_cmp_ret ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				$ls_codemp= $row["codemp"]; 
				$ls_tipdoc= $row["tipdoc"];
				$ls_id_doc= $row["id_doc"];
				$ls_numretdoc= $row["numretdoc"];

				$li_totcmp_sin_iva= $row["totcmp_sin_iva"];
				$li_totcmp_con_iva= $row["totcmp_con_iva"];
				$li_basimp= $row["basimp"];
				$li_monimp= $row["monimp"];
				$li_monret= $row["monret"];
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","totcmp_sin_iva");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_totcmp_sin_iva);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","totcmp_con_iva");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_totcmp_con_iva);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","basimp");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_basimp);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","monimp");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_monimp);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","monret");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_monret);

	
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codemp");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codemp);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","tipdoc");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_tipdoc);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_doc");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_doc);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","numretdoc");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_numretdoc);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_cmp_ret",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxc_cmp_ret

	//-----------------------------------------------------------------------------------------------------------------------------
	function uf_convertir_cxc_cotiza_pedidos() 
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxc_cotiza_pedidos
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_cotiza_pedidos e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT codemp, id_cotped,".
				" montodesc, saldo, subtot, iva, otros, baseimp, total".
				"  FROM cxc_cotiza_pedidos".
				" WHERE codemp='".$this->ls_codemp."'";

		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_cotiza_pedidos ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				$ls_codemp= $row["codemp"]; 
				$ls_id_cotped= $row["id_cotped"];

				$li_montodesc= $row["montodesc"];
				$li_saldo= $row["saldo"];
				$li_subtot= $row["subtot"];
				$li_iva= $row["iva"];
				$li_otros= $row["otros"];
				$li_baseimp= $row["baseimp"];
				$li_total= $row["total"];
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","montodesc");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_montodesc);
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","saldo");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_saldo);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","subtot");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_subtot);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","iva");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_iva);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","otros");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_otros);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","baseimp");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_baseimp);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","total");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_total);

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codemp");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codemp);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_cotped");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_cotped);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_cotiza_pedidos",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxc_cotiza_pedidos

	function uf_convertir_cxc_detalle() 
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxcanticipos
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_detalle e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT  id_fact, codproceso, renglon,".
				" precio_detalle, iva_detalle, neto_detalle, otros_detalle".
				"  FROM cxc_detalle";
				//" WHERE codemp='".$this->ls_codemp."'";

		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_detalle ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				//$ls_codemp= $row["codemp"]; 
				$ls_id_fact= $row["id_fact"];
				$ls_codproceso = $row["codproceso"];

				$li_precio_detalle= $row["precio_detalle"];
				$li_iva_detalle= $row["iva_detalle"];
				$li_neto_detalle= $row["neto_detalle"];
				$li_otros_detalle= $row["otros_detalle"];
				
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","precio_detalle");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_precio_detalle);
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","iva_detalle");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_iva_detalle);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","neto_detalle");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_neto_detalle);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","otros_detalle");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_otros_detalle);


				// $this->io_rcbsf->io_ds_filtro->insertRow("filtro","codemp");
				// $this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codemp);
				// $this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_fact");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_fact);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codproceso");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codproceso);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_detalle",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxcanticipos

	//////////////***********************************************************************************************************************************************************************************************************************************************************************************************************
	function uf_convertir_cxc_dt_anticipos() 
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxc_dt_anticipos
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_dt_anticipos e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT  codemp, id_cliente, nroant, nrodesc,".
				" montodesc".
				"  FROM cxc_dt_anticipos".
				" WHERE codemp='".$this->ls_codemp."'";

		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_dt_anticipos ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				$ls_codemp= $row["codemp"]; 
				$ls_id_cliente= $row["id_cliente"];
				$ls_nroant = $row["nroant"];
				$ls_nrodesc = $row["nrodesc"];

				$li_montodesc= $row["montodesc"];

				
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","montodesc");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_montodesc);
				

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codemp");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codemp);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_cliente");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_cliente);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","nroant");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_nroant);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","nrodesc");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_nrodesc);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_dt_anticipos",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxc_dt_anticipos
		//////////////***********************************************************************************************************************************************************************************************************************************************************************************************************


	function uf_convertir_cxc_dt_cargos() 
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxc_dt_cargos
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_dt_cargos e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT  codemp, codproceso, id_doc, id_fact, codcar, ".
				" monbasimp, monimp, montot".
				"  FROM cxc_dt_cargos".
				" WHERE codemp='".$this->ls_codemp."'";

		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_dt_cargos ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				$ls_codemp= $row["codemp"]; 
				$ls_codproceso= $row["codproceso"];
				$ls_id_doc = $row["id_doc"];
				$ls_id_fact = $row["id_fact"];
				$ls_codcar = $row["codcar"];


				$li_monbasimp= $row["monbasimp"];
				$li_montot= $row["montot"];
				$li_monimp= $row["monimp"];

				
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","monimp");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_monimp);
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","montot");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_montot);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","monbasimp");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_monbasimp);

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codemp");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codemp);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codproceso");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codproceso);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_doc");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_doc);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codcar");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codcar);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_fact");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_fact);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");


				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_dt_cargos",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxc_dt_anticipos

	function uf_convertir_cxc_dt_cargos_cotped() 
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxc_dt_cargos_cotped
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_dt_cargos_cotped e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT  codemp, id_cotped, codcar, ".
				" monbasimp, monimp, montot".
				"  FROM cxc_dt_cargos_cotped".
				" WHERE codemp='".$this->ls_codemp."'";
				// echo $ls_sql;
				// exit();
		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_dt_cargos_cotped ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				$ls_codemp= $row["codemp"]; 
				$ls_id_cotped= $row["id_cotped"];
				// $ls_id_doc = $row["id_doc"];
				// $ls_id_fact = $row["id_fact"];
				$ls_codcar = $row["codcar"];


				$li_monbasimp= $row["monbasimp"];
				$li_montot= $row["montot"];
				$li_monimp= $row["monimp"];

				
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","monimp");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_monimp);
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","montot");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_montot);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","monbasimp");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_monbasimp);

				////////////////////////////////////FILTROS /////////////////////////

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codemp");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codemp);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_cotped");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_cotped);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				// $this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_doc");
				// $this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_doc);
				// $this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codcar");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codcar);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				// $this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_fact");
				// $this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_fact);
				// $this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");


				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_dt_cargos_cotped",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxc_dt_anticipos

	function uf_convertir_cxc_dt_cotiza_pedidos() 
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxc_dt_cotiza_pedidos
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_dt_cotiza_pedidos e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT id_cotped, renglon, ".
				" precio_detalle, iva_detalle, neto_detalle, otros_detalle".
				"  FROM cxc_dt_cotiza_pedidos";
				// " WHERE codemp='".$this->ls_codemp."'";
				// echo $ls_sql;
				// exit();
		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_dt_cotiza_pedidos ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				// $ls_codemp= $row["codemp"]; 
				$ls_id_cotped= $row["id_cotped"];
				// $ls_id_doc = $row["id_doc"];
				// $ls_id_fact = $row["id_fact"];
				$ls_renglon = $row["renglon"];


				$li_precio_detalle= $row["precio_detalle"];
				$li_iva_detalle= $row["iva_detalle"];
				$li_neto_detalle= $row["neto_detalle"];
				$li_otros_detalle= $row["otros_detalle"];

				
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","otros_detalle");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_otros_detalle);
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","iva_detalle");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_iva_detalle);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","neto_detalle");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_neto_detalle);


				$this->io_rcbsf->io_ds_datos->insertRow("campo","precio_detalle");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_precio_detalle);

				////////////////////////////////////FILTROS /////////////////////////

				// $this->io_rcbsf->io_ds_filtro->insertRow("filtro","codemp");
				// $this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codemp);
				// $this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_cotped");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_cotped);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				// $this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_doc");
				// $this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_doc);
				// $this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","renglon");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_renglon);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				// $this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_fact");
				// $this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_fact);
				// $this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");


				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_dt_cotiza_pedidos",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxc_dt_anticipos

	function uf_convertir_cxc_dt_movcobro() 
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxc_dt_movcobro
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_dt_movcobro e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT codemp, tipdoc, id_doc, nromovcob, ".
				" monmovcob ".
				"  FROM cxc_dt_movcobro".
				" WHERE codemp='".$this->ls_codemp."'";
				// echo $ls_sql;
				// exit();
		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_dt_movcobro ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				$ls_codemp= $row["codemp"]; 
				$ls_tipdoc= $row["tipdoc"];
				// $ls_id_doc = $row["id_doc"];
				$ls_nromovcob = $row["nromovcob"];
				$ls_id_doc = $row["id_doc"];


				$li_monmovcob= $row["monmovcob"];
				// $li_iva_detalle= $row["iva_detalle"];
				// $li_neto_detalle= $row["neto_detalle"];
				// $li_otros_detalle= $row["otros_detalle"];

				
				
				// $this->io_rcbsf->io_ds_datos->insertRow("campo","otros_detalle");
				// $this->io_rcbsf->io_ds_datos->insertRow("monto",$li_otros_detalle);
				
				// $this->io_rcbsf->io_ds_datos->insertRow("campo","iva_detalle");
				// $this->io_rcbsf->io_ds_datos->insertRow("monto",$li_iva_detalle);

				// $this->io_rcbsf->io_ds_datos->insertRow("campo","neto_detalle");
				// $this->io_rcbsf->io_ds_datos->insertRow("monto",$li_neto_detalle);


				$this->io_rcbsf->io_ds_datos->insertRow("campo","monmovcob");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_monmovcob);

				////////////////////////////////////FILTROS /////////////////////////

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codemp");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codemp);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","tipdoc");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_tipdoc);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				// $this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_doc");
				// $this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_doc);
				// $this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_doc");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_doc);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","nromovcob");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_nromovcob);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");


				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_dt_movcobro",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxc_dt_anticipos

	function uf_convertir_cxc_dt_scg() 
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxc_dt_movcobro
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_dt_scg e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT codemp, procede, comprobante, sc_cuenta, fecha, cod_pro, ced_bene, ".
				" monto ".
				"  FROM cxc_dt_scg".
				" WHERE codemp='".$this->ls_codemp."'";
				// echo $ls_sql;
				// exit();
		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_dt_scg ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				$ls_codemp= $row["codemp"]; 
				$ls_procede= $row["procede"];
				$ls_sc_cuenta = $row["sc_cuenta"];
				$ls_fecha = $row["fecha"];
				$ls_comprobante = $row["comprobante"];
				$ls_cod_pro = $row["cod_pro"];
				$ls_ced_bene = $row["ced_bene"];
				
				$li_monto= $row["monto"];
				// $li_iva_detalle= $row["iva_detalle"];
				// $li_neto_detalle= $row["neto_detalle"];
				// $li_otros_detalle= $row["otros_detalle"];
				
				// $this->io_rcbsf->io_ds_datos->insertRow("campo","otros_detalle");
				// $this->io_rcbsf->io_ds_datos->insertRow("monto",$li_otros_detalle);
				
				// $this->io_rcbsf->io_ds_datos->insertRow("campo","iva_detalle");
				// $this->io_rcbsf->io_ds_datos->insertRow("monto",$li_iva_detalle);

				// $this->io_rcbsf->io_ds_datos->insertRow("campo","neto_detalle");
				// $this->io_rcbsf->io_ds_datos->insertRow("monto",$li_neto_detalle);


				$this->io_rcbsf->io_ds_datos->insertRow("campo","monto");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_monto);

				////////////////////////////////////FILTROS /////////////////////////

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codemp");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codemp);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","procede");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_procede);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","sc_cuenta");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_sc_cuenta);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","fecha");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_fecha);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","comprobante");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_comprobante);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","cod_pro");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_cod_pro);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","ced_bene");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_ced_bene);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_dt_scg",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxc_dt_anticipos

	function uf_convertir_cxc_dt_spi() 
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxc_dt_movcobro
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_dt_spi e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT codemp, procede, comprobante, fecha, operacion, spi_cuenta, codestpro1, codestpro2,".
				" codestpro3, codestpro4, codestpro5, estcla, ".
				" monto ".
				"  FROM cxc_dt_spi".
				" WHERE codemp='".$this->ls_codemp."'";
				// echo $ls_sql;
				// exit();
		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_dt_spi ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				$ls_codemp= $row["codemp"]; 
				$ls_procede= $row["procede"];
				$ls_spi_cuenta = $row["spi_cuenta"];
				$ls_operacion = $row["operacion"];
				$ls_fecha = $row["fecha"];
				$ls_comprobante = $row["comprobante"];
				$ls_codestpro1 = $row["codestpro1"];
				$ls_codestpro2 = $row["codestpro2"];
				$ls_codestpro3 = $row["codestpro3"];
				$ls_codestpro4 = $row["codestpro4"];
				$ls_codestpro5 = $row["codestpro5"];
				$ls_estcla = $row["estcla"];
				// **************    VARIABLES CAMPOS
				$li_monto= $row["monto"];
				// $li_iva_detalle= $row["iva_detalle"];
				// $li_neto_detalle= $row["neto_detalle"];
				// $li_otros_detalle= $row["otros_detalle"];
				
				// $this->io_rcbsf->io_ds_datos->insertRow("campo","otros_detalle");
				// $this->io_rcbsf->io_ds_datos->insertRow("monto",$li_otros_detalle);
				
				// $this->io_rcbsf->io_ds_datos->insertRow("campo","iva_detalle");
				// $this->io_rcbsf->io_ds_datos->insertRow("monto",$li_iva_detalle);

				// $this->io_rcbsf->io_ds_datos->insertRow("campo","neto_detalle");
				// $this->io_rcbsf->io_ds_datos->insertRow("monto",$li_neto_detalle);


				$this->io_rcbsf->io_ds_datos->insertRow("campo","monto");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_monto);

				////////////////////////////////////FILTROS /////////////////////////

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codemp");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codemp);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","procede");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_procede);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","spi_cuenta");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_spi_cuenta);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","operacion");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_operacion);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","fecha");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_fecha);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","comprobante");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_comprobante);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codestpro1");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codestpro1);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codestpro2");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codestpro2);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codestpro3");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codestpro3);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codestpro4");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codestpro4);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codestpro5");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codestpro5);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","estcla");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_estcla);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_dt_spi",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxc_dt_anticipos

	function uf_convertir_cxc_dt_spg() 
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxc_dt_movcobro
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_dt_spg e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT codemp, procede, comprobante, spg_cuenta, fecha, cod_pro, ced_bene, ".
				" monto ".
				"  FROM cxc_dt_spg".
				" WHERE codemp='".$this->ls_codemp."'";
				// echo $ls_sql;
				// exit();
		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_dt_spg ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				$ls_codemp= $row["codemp"]; 
				$ls_procede= $row["procede"];
				$ls_spg_cuenta = $row["spg_cuenta"];
				$ls_fecha = $row["fecha"];
				$ls_comprobante = $row["comprobante"];
				$ls_cod_pro = $row["cod_pro"];
				$ls_ced_bene = $row["ced_bene"];
				
				$li_monto= $row["monto"];
				// $li_iva_detalle= $row["iva_detalle"];
				// $li_neto_detalle= $row["neto_detalle"];
				// $li_otros_detalle= $row["otros_detalle"];
				
				// $this->io_rcbsf->io_ds_datos->insertRow("campo","otros_detalle");
				// $this->io_rcbsf->io_ds_datos->insertRow("monto",$li_otros_detalle);
				
				// $this->io_rcbsf->io_ds_datos->insertRow("campo","iva_detalle");
				// $this->io_rcbsf->io_ds_datos->insertRow("monto",$li_iva_detalle);

				// $this->io_rcbsf->io_ds_datos->insertRow("campo","neto_detalle");
				// $this->io_rcbsf->io_ds_datos->insertRow("monto",$li_neto_detalle);


				$this->io_rcbsf->io_ds_datos->insertRow("campo","monto");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_monto);

				////////////////////////////////////FILTROS /////////////////////////

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codemp");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codemp);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","procede");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_procede);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","spg_cuenta");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_spg_cuenta);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","fecha");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_fecha);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","comprobante");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_comprobante);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","cod_pro");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_cod_pro);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","ced_bene");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_ced_bene);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_dt_spg",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxc_dt_anticipos

	function uf_convertir_cxc_factura() 
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxc_factura
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_factura e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT id_fact, ".
				" montodesc, saldo, subtot, iva, otros, baseimp, total".
				"  FROM cxc_factura";
				// " WHERE codemp='".$this->ls_codemp."'";

		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_factura ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				$ls_id_fact= $row["id_fact"];

				$li_montodesc= $row["montodesc"];
				$li_saldo= $row["saldo"];
				$li_subtot= $row["subtot"];
				$li_iva= $row["iva"];
				$li_otros= $row["otros"];
				$li_baseimp= $row["baseimp"];
				$li_total= $row["total"];
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","montodesc");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_montodesc);
				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","saldo");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_saldo);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","subtot");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_subtot);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","iva");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_iva);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","otros");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_otros);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","baseimp");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_baseimp);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","total");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_total);

				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_fact");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_fact);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_factura",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxc_cotiza_pedidos

	function uf_convertir_cxc_ingresos() 
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxc_ingresos
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_ingresos e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT codemp, codsuc, codcaj, nroing, ".
				" moning ".
				"  FROM cxc_ingresos".
				" WHERE codemp='".$this->ls_codemp."'";

		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_ingresos ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				$ls_codsuc= $row["codsuc"];
				$ls_codcaj= $row["codcaj"];
				$ls_nroing= $row["nroing"];
				$ls_codemp= $row["codemp"];

				$li_moning= $row["moning"];

				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","moning");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_moning);

				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codsuc");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codsuc);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codcaj");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codcaj);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","nroing");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_nroing);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codemp");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codemp);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_ingresos",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxc_cotiza_pedidos

	function uf_convertir_cxc_movbaco_asoc() 
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxc_movbaco_asoc
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_movbaco_asoc e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT codemp,codban, ctaban, numdoc, codope, estmov, codsuc, codcaj, nroing, tipdoc, id_doc, nromovcob,".
				" estmovasoc,".
				" monto ".
				"  FROM cxc_movbaco_asoc".
				" WHERE codemp='".$this->ls_codemp."'";
				// echo $ls_sql;
				// exit();
		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_movbaco_asoc ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				$ls_codemp= $row["codemp"];
				$ls_codban= $row["codban"];
				$ls_ctaban= $row["ctaban"];
				$ls_numdoc= $row["numdoc"];
				$ls_codope= $row["codope"];
				$ls_estmov= $row["estmov"];
				$ls_codsuc= $row["codsuc"];
				$ls_codcaj= $row["codcaj"];
				$ls_nroing= $row["nroing"];
				$ls_tipdoc= $row["tipdoc"];
				$ls_id_doc= $row["id_doc"];
				$ls_nromovcob= $row["nromovcob"];
				$ls_estmovasoc= $row["estmovasoc"];

				// variables montos.
				$li_monto= $row["monto"];

				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","monto");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_monto);

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codemp");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codemp);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");
				
				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codban");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codban);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","ctaban");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_ctaban);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","numdoc");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_numdoc);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codope");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codope);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","estmov");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_estmov);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codsuc");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codsuc);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","codcaj");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_codcaj);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","nroing");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_nroing);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","tipdoc");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_tipdoc);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","id_doc");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_id_doc);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","nromovcob");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_nromovcob);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","estmovasoc");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_estmovasoc);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");



				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_movbaco_asoc",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxc_cotiza_pedidos

	function uf_convertir_cxc_movimientos() 
	{
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//	     Function: uf_convertir_cxc_movimientos
		//		   Access: private
		//	      Returns: lb_valido True si se ejecuto el insert ó False si hubo error en el insert
		//	  Description: Funcion que selecciona los campos de moneda de la tabla cxc_movimientos e inserta el valor reconvertido
		//	   Creado Por: santi consultores
		// Fecha Creación: 13/05/2018 								Fecha Última Modificación : 
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$lb_valido=true;
		$ls_sql="SELECT idmov,".
				" total, totalnograb, baseimp, monimp, ivaret, ivaper ".
				"  FROM cxc_movimientos";
				// " WHERE codemp='".$this->ls_codemp."'";
				// echo $ls_sql;
				// exit();
		$rs_data=$this->io_sql_origen->select($ls_sql);
		if($rs_data===false)
		{ 
			$this->io_mensajes->message("CLASE->sigesp_rcm_c_cxc MÉTODO->SELECT->uf_convertir_cxc_movimientos ERROR->".$this->io_funciones->uf_convertirmsg($this->io_sql->message));
			$lb_valido=false;
		}
		else
		{
			$la_seguridad="";
			while(($row=$this->io_sql_origen->fetch_row($rs_data))&&($lb_valido))
			{
				$ls_idmov= $row["idmov"];

				// variables montos.
				$li_total= $row["total"];
				$li_totalnograb= $row["totalnograb"];
				$li_baseimp= $row["baseimp"];
				$li_monimp= $row["monimp"];
				$li_ivaret= $row["ivaret"];
				$li_ivaper= $row["ivaper"];

				
				$this->io_rcbsf->io_ds_datos->insertRow("campo","total");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_total);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","totalnograb");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_totalnograb);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","baseimp");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_baseimp);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","monimp");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_monimp);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","ivaret");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_ivaret);

				$this->io_rcbsf->io_ds_datos->insertRow("campo","ivaper");
				$this->io_rcbsf->io_ds_datos->insertRow("monto",$li_ivaper);

				$this->io_rcbsf->io_ds_filtro->insertRow("filtro","idmov");
				$this->io_rcbsf->io_ds_filtro->insertRow("valor",$ls_idmov);
				$this->io_rcbsf->io_ds_filtro->insertRow("tipo","C");

				$lb_valido=$this->io_rcbsf->uf_reconvertir_datos("cxc_movimientos",$la_seguridad);
			}
		}		
		return $lb_valido;
	}// end function uf_convertir_cxc_cotiza_pedidos


}
?>