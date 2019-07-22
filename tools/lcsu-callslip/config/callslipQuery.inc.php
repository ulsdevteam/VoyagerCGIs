<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

# Get list of Callslips.
$callslipQuery = "select cs.call_slip_id,
	cs.bib_id,
	cs.item_id,
	cs.mfhd_id,
	cs.patron_id,
	cs.patron_group_id,
	(select pgrp.patron_group_code
		from pittdb.patron_group pgrp
		where pgrp.patron_group_id = cs.patron_group_id) patron_group_code,
	to_char(cs.date_requested, 'yyyy/mm/dd hh24:mi:ss') date_requested,
	to_char(cs.date_processed, 'yyyy/mm/dd hh24:mi:ss') date_processed,
	cs.location_id,
	cs.status call_slip_status,
	(select csst.status_desc
		from pittdb.call_slip_status_type csst
		where csst.status_type = cs.status) call_slip_status_type,
	to_char(cs.status_date, 'yyyy/mm/dd') status_date,
	cs.status_opid,
	cs.no_fill_reason,
	cs.item_year,
	cs.item_enum,
	cs.item_chron,
	cs.note,
	cs.pickup_location_id,
	(select l.location_code
		from pittdb.location l
		where l.location_id = cs.pickup_location_id) pickup_location_code,
	(select l.location_display_name
		from pittdb.location l
		where l.location_id = cs.pickup_location_id) pickup_location_disp,
	(select l.location_name
		from pittdb.location l
		where l.location_id = cs.pickup_location_id) pickup_location_name,
	upper(i.spine_label) spine_label,
	i.perm_location,
	(select l.location_code
		from pittdb.location l
		where l.location_id = i.perm_location) perm_location_code,
	i.temp_location,
	(select l.location_code
		from pittdb.location l
		where l.location_id = i.temp_location) temp_location_code,
	i.item_type_id perm_item_type_id,
	(select it.item_type_code
		from pittdb.item_type it, pittdb.item i
		where it.item_type_id = i.item_type_id
		and i.item_id = cs.item_id) perm_item_type_code,
	i.temp_item_type_id,
	(select it.item_type_code
		from pittdb.item_type it, pittdb.item i
		where it.item_type_id = i.temp_item_type_id
		and i.item_id = cs.item_id) temp_item_type_code,
	mm.location_id,
	(select l.location_code
		from pittdb.location l
		where l.location_id = mm.location_id) mfhd_location_code,
	mm.display_call_no,
	ib.item_barcode,
	p.last_name,
	p.first_name,
	p.middle_name,
	(select pb.patron_barcode
		from pittdb.patron_barcode pb
		where pb.patron_id = cs.patron_id
		and pb.barcode_status = 1
		fetch first 1 rows only) patron_barcode,
	bt.author,
	bt.title,
	substr(bt.title_brief,1,30) title_brief,
	(select pa.address_line1
		from pittdb.patron_address pa
		where pa.patron_id = cs.patron_id
		and pa.address_type = 2
		fetch first 1 rows only) patron_campus_address,
	(select pp.phone_number
		from pittdb.patron_phone pp,
			pittdb.patron_address pa
		where pp.address_id = pa.address_id
		and pa.patron_id = cs.patron_id
		and pa.address_type = 2
		fetch first 1 rows only) patron_campus_phone,
	(select count(*)
		from pittdb.mfhd_item mi
		where mi.mfhd_id = cs.mfhd_id) mfhd_item_count
from pittdb.call_slip cs
left join pittdb.item i on i.item_id = cs.item_id
left join pittdb.mfhd_master mm on mm.mfhd_id = cs.mfhd_id
left outer join pittdb.item_barcode ib on ib.item_id = cs.item_id and ib.barcode_status = 1
left join pittdb.patron p on p.patron_id = cs.patron_id
left join pittdb.bib_text bt on bt.bib_id = cs.bib_id
where
	cs.print_group_id = '20' 
	and cs.status in (1,3) 
order by i.spine_label";	
