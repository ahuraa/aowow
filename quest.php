<?php

// ���������� ������� questinfo
require_once('includes/allquests.php');
require_once('includes/allobjects.php');
require_once('includes/allnpcs.php');
require_once('includes/allcomments.php');
require_once('includes/allachievements.php');
require_once('includes/allevents.php');

$smarty->config_load($conf_file, 'quest');

// ����� ������
$id = intval($podrazdel);

$cache_key = cache_key($id);

if(!$quest = load_cache(QUEST_PAGE, $cache_key))
{
	unset($quest);

	// �������� ����
	$quest = GetDBQuestInfo($id, 0xFFFFFF);


	/*              ������� �������              */
	// ��������� ��� ����� � �������
	$quest['series'] = array(
		array(
			'entry' => $quest['entry'],
			'Title' => $quest['Title'],
			'NextQuestIdChain' => $quest['NextQuestIdChain']
			)
	);
	// ������ � ������� �� ����� ������
	$tmp = $quest['series'][0];
	while($tmp)
	{
		$tmp = $DB->selectRow('
			SELECT q.entry, q.Title
				{, l.Title_loc?d as Title_loc}
			FROM v_quest_template q
				{LEFT JOIN (locales_quest l) ON l.Id=q.entry AND ?d}
			WHERE q.NextQuestIdChain=?d
			LIMIT 1
			',
			($_SESSION['locale']>0)? $_SESSION['locale']: DBSIMPLE_SKIP,
			($_SESSION['locale']>0)? 1: DBSIMPLE_SKIP,
			$quest['series'][0]['entry']
		);
		if($tmp)
		{
			$tmp['Title'] = localizedName($tmp, 'Title');
			array_unshift($quest['series'], $tmp);
		}
	}
	
	// ������ � ������� ����� ����� ������
	$tmp = end($quest['series']);
	while($tmp)
	{
		$tmp = $DB->selectRow('
			SELECT q.entry, q.Title, q.NextQuestIdChain
				{, l.Title_loc?d as Title_loc}
			FROM v_quest_template q
				{LEFT JOIN (locales_quest l) ON l.Id=q.entry AND ?}
			WHERE q.entry=?d
			LIMIT 1
			',
			($_SESSION['locale']>0)? $_SESSION['locale']: DBSIMPLE_SKIP,
			($_SESSION['locale']>0)? 1: DBSIMPLE_SKIP,
			$quest['series'][count($quest['series'])-1]['NextQuestIdChain']
		);
		if($tmp)
		{
			$tmp['Title'] = localizedName($tmp, 'Title');
			array_push($quest['series'], $tmp);
		}
	}
	unset($tmp);
	if(count($quest['series'])<=1)
		unset($quest['series']);
	

	/*              ������ ������              */
	// (����� �� ���������� ��������� �� ������ �� ������� �����������)


	// ������, ������� ���������� ���������, ��� �� �������� ���� �����
	if(!$quest['req'] = $DB->select('
				SELECT q.entry, q.Title, q.NextQuestIdChain
					{, l.Title_loc?d as Title_loc}
				FROM v_quest_template q
					{LEFT JOIN (locales_quest l) ON l.Id=q.entry AND ?}
				WHERE
					(q.NextQuestID=?d AND q.ExclusiveGroup<0)
					OR (q.entry=?d AND q.NextQuestIdChain<>?d)
				LIMIT 20',
				($_SESSION['locale']>0)? $_SESSION['locale']: DBSIMPLE_SKIP, ($_SESSION['locale']>0)? 1: DBSIMPLE_SKIP,
				$quest['entry'], $quest['PrevQuestId'], $quest['entry']
				)
		)
			unset($quest['req']);
		else
			$questItems[] = 'req';

	// ������, ������� ���������� ����������, ������ ����� ���� ��� �������� ���� ����� (������������� ������ ��)
	if(!$quest['open'] = $DB->select('
				SELECT q.entry, q.Title
					{, l.Title_loc?d as Title_loc}
				FROM v_quest_template q
					{LEFT JOIN (locales_quest l) ON l.Id=q.entry AND ?}
				WHERE
					(q.PrevQuestId=?d AND q.entry<>?d)
					OR q.entry=?d
				LIMIT 20',
				($_SESSION['locale']>0)? $_SESSION['locale']: DBSIMPLE_SKIP, ($_SESSION['locale']>0)? 1: DBSIMPLE_SKIP,
				$quest['entry'], $quest['NextQuestIdChain'], $quest['NextQuestID']
				)
		)
			unset($quest['open']);
		else
			$questItems[] = 'open';
		
	// ������, ������� ���������� ������������ ����� ���������� ����� ������
	if($quest['ExclusiveGroup']>0)
		if(!$quest['closes'] = $DB->select('
				SELECT q.entry, q.Title
					{, l.Title_loc?d as Title_loc}
				FROM v_quest_template q
					{LEFT JOIN (locales_quest l) ON l.Id=q.entry AND ?}
				WHERE
					q.ExclusiveGroup=?d AND q.entry<>?d
				LIMIT 20
				',
				($_SESSION['locale']>0)? $_SESSION['locale']: DBSIMPLE_SKIP, ($_SESSION['locale']>0)? 1: DBSIMPLE_SKIP,
				$quest['ExclusiveGroup'], $quest['entry']
				)
		)
			unset($quest['closes']);
		else
			$questItems[] = 'closes';

	// ������� ���������� ������ �� �������, �� �����:
	if(!$quest['reqone'] = $DB->select('
				SELECT q.entry, q.Title
					{, l.Title_loc?d as Title_loc}
				FROM v_quest_template q
					{LEFT JOIN (locales_quest l) ON l.Id=q.entry AND ?}
				WHERE
					q.ExclusiveGroup>0 AND q.NextQuestId=?d
				LIMIT 20
				',
				($_SESSION['locale']>0)? $_SESSION['locale']: DBSIMPLE_SKIP, ($_SESSION['locale']>0)? 1: DBSIMPLE_SKIP,
				$quest['entry']
				)
		)
			unset($quest['reqone']);
		else
			$questItems[] = 'reqone';
		
	// ������, ������� ��������, ������ �� ����� ���������� ����� ������
	if(!$quest['enables'] = $DB->select('
				SELECT q.entry, q.Title
					{, l.Title_loc?d as Title_loc}
				FROM v_quest_template q
					{LEFT JOIN (locales_quest l) ON l.Id=q.entry AND ?}
				WHERE q.PrevQuestId=?d
				LIMIT 20
				',
				($_SESSION['locale']>0)? $_SESSION['locale']: DBSIMPLE_SKIP, ($_SESSION['locale']>0)? 1: DBSIMPLE_SKIP,
				-$quest['entry']
				)
		)
			unset($quest['enables']);
		else
			$questItems[] = 'enables';
		
	// ������, �� ����� ���������� ������� �������� ���� �����
	if($quest['PrevQuestId']<0)
		if(!$quest['enabledby'] = $DB->select('
				SELECT q.entry, q.Title
					{, l.Title_loc?d as Title_loc}
				FROM v_quest_template q
					{LEFT JOIN (locales_quest l) ON l.Id=q.entry AND ?}
				WHERE q.entry=?d
				LIMIT 20
				',
				($_SESSION['locale']>0)? $_SESSION['locale']: DBSIMPLE_SKIP, ($_SESSION['locale']>0)? 1: DBSIMPLE_SKIP,
				-$quest['PrevQuestId']
				)
		)
			unset($quest['enabledby']);
		else
			$questItems[] = 'enabledby';

	// ������ ���������� ��� ������ �������
	if($questItems)
		foreach($questItems as $item)
			foreach($quest[$item] as $i => $x)
				$quest[$item][$i]['Title'] = localizedName($quest[$item][$i], 'Title');



	/*             ������� � ����������             */

	if($quest['RequiredSkillValue']>0 && $quest['RequiredSkill']>0)
	{
		// ��������� ������� ������, ��� �� �������� �����
		/*
		$skills = array(
			-264 => 197,	// Tailoring
			-182 => 165,	// Leatherworking
			-24 => 182,		// Herbalism
			-101 => 356,	// Fishing
			-324 =>	129,	// First Aid
			-201 => 202,	// Engineering
			-304 => 185,	// Cooking
			-121 => 164,	// Blacksmithing
			-181 => 171		// Alchemy
		);
		*/
		
		// TODO: skill localization
		$quest['reqskill'] = array(
			'name' => $DB->selectCell('SELECT name_loc'.$_SESSION['locale'].' FROM ?_skill WHERE skillID=?d LIMIT 1',$quest['RequiredSkillid']),
			'value' => $quest['RequiredSkillValue']
		);
	}
	elseif($quest['RequiredClasses']>0)
	{
		$s = array();
		foreach($classes as $i => $class)
			if (intval(-$quest['RequiredClasses']) & (1<<$i))
				$s[] = $class;
		if (count($s) == 0) $s[] = "UNKNOWN";
		// ��������� �����, ��� �� �������� �����
		$quest['reqclass'] = implode(", ", $s);
	}
	
	// ��������� ��������� � ���������, ��� �� ������ �����
	if($quest['RequiredMinRepFaction'])
		$quest['RequiredMinRep'] = array(
			'name' => $DB->selectCell('SELECT name_loc'.$_SESSION['locale'].' FROM ?_factions WHERE factionID=?d LIMIT 1', $quest['RequiredMinRepFaction']),
			'entry' => $quest['RequiredMinRepFaction'],
			'value' => reputations($quest['RequiredMinRepValue'])
		);
	if($quest['RequiredMaxRepFaction'])
		$quest['RequiredMaxRep'] = array(
			'name' => $DB->selectCell('SELECT name_loc'.$_SESSION['locale'].' FROM ?_factions WHERE factionID=?d LIMIT 1', $quest['RequiredMaxRepFaction']),
			'entry' => $quest['RequiredMaxRepFaction'],
			'value' => reputations($quest['RequiredMaxRepValue'])
		);
	
	// ������ �� ������� �����������, �� ���� ������� �� ����
	// ������� � ���� ��� ����������� - ������� �� ��������

	// �����, ��������� �� ������ � ������ ������
	if($quest['SourceSpellId'])
	{
		$tmp = $DB->selectRow('
			SELECT ?#, s.spellname_loc'.$_SESSION['locale'].'
			FROM ?_spell s, ?_spellicons si
			WHERE
				s.spellID=?d
				AND si.id=s.spellicon
			LIMIT 1',
			$spell_cols[0],
			$quest['SourceSpellId']
		);
		if($tmp)
		{
			$quest['SourceSpellId'] = array(
				'name' => $tmp['spellname_loc'.$_SESSION['locale']],
				'entry' => $tmp['spellID']);
			allspellsinfo2($tmp);
		}
		unset($tmp);
	}
	
	// ����, ���������� ������ � ������ ������
	if($quest['SourceItemId'])
	{
		$quest['SrcItem'] = iteminfo($quest['SourceItemId']);
		$quest['SrcItem']['count'] = $quest['SourceItemCount'];
	}
	
	// �������������� ���������� � ������ (�����, �������������, �������)
	$quest['flagsdetails'] = GetFlagsDetails($quest);
	if (!$quest['flagsdetails'])
		unset($quest['flagsdetails']);

	// �����, ��������� �� ������ � ������� �� ����������
	if($quest['RewardSpellCast']>0 || $quest['RewardSpell']>0)
	{
		$tmp = $DB->SelectRow('
			SELECT ?#, s.spellname_loc'.$_SESSION['locale'].'
			FROM ?_spell s, ?_spellicons si
			WHERE
				s.spellID=?d
				AND si.id=s.spellicon
			LIMIT 1',
			$spell_cols[0],
			$quest['RewardSpell']>0?$quest['RewardSpell']:$quest['RewardSpellCast']
		);
		if($tmp)
		{
			$quest['spellreward'] = array(
				'name' => $tmp['spellname_loc'.$_SESSION['locale']],
				'entry' => $tmp['spellID'],
				'realentry' => $quest['RewSpellCast']>0 ? $quest['RewardSpellCast'] : $quest['RewardSpell']);
			allspellsinfo2($tmp);
		}
		unset($tmp);
	}

	// ��������, ����������� ��� ������
	//$quest['creaturereqs'] = array();
	//$quest['objectreqs'] = array();
	$quest['coreqs'] = array();
	for($i=0;$i<=4;++$i)
	{
		//echo $quest['RequiredNpcOrGoCount'.$i].'<br />';
		if($quest['RequiredNpcOrGo'.$i] != 0 && $quest['RequiredNpcOrGoCount'.$i] != 0)
		{
			if($quest['RequiredNpcOrGo'.$i] > 0)
			{
				// ���������� �����-���� ������������� � ���������
				$quest['coreqs'][$i] = array_merge(
					creatureinfo($quest['RequiredNpcOrGo'.$i]),
					array('req_type' => 'npc')
				);
			}
			else
			{
				// ���������� �����-�� �������������� � ��������
				$quest['coreqs'][$i] = @array_merge(
					objectinfo(-$quest['RequiredNpcOrGo'.$i]),
					array('req_type' => 'object')
				);
			}
			// ����������
			$quest['coreqs'][$i]['count'] = $quest['RequiredNpcOrGoCount'.$i];
			// �����
			if($quest['RequiredSpellCast'.$i])
				$quest['coreqs'][$i]['spell'] = array(
					'name' => $DB->selectCell('SELECT spellname_loc'.$_SESSION['locale'].' FROM ?_spell WHERE spellid=?d LIMIT 1', $quest['RequiredSpellCast'.$i]),
					'entry' => $quest['RequiredSpellCast'.$i]
				);
		}
	}
	if(!$quest['coreqs'])
		unset($quest['coreqs']);

	// ����, ����������� ��� ������
	$quest['itemreqs'] = array();
	for($i=0;$i<=4;++$i)
	{
		if($quest['RequiredItemId'.$i]!=0 && $quest['RequiredItemCount'.$i]!=0)
			$quest['itemreqs'][] = @array_merge(iteminfo($quest['RequiredItemId'.$i]), array('count' => $quest['RequiredItemCount'.$i]));
	}
	if(!$quest['itemreqs'])
		unset($quest['itemreqs']);

	// ������� ����������� ��� ������
	if($quest['RepObjectiveFaction']>0)
	{
		$quest['factionreq'] = array(
			'name' => $DB->selectCell('SELECT name_loc'.$_SESSION['locale'].' FROM ?_factions WHERE factionID=?d LIMIT 1', $quest['RepObjectiveFaction']),
			'entry' => $quest['RepObjectiveFaction'],
			'value' => reputations($quest['RepObjectiveValue'])
		);
	}

	/* ����������� � ������������ */

	// �����������
	// ���
	$rows = $DB->select('
		SELECT c.entry, c.name, A, H
			{, l.name_loc?d AS name_loc}
		FROM creature_questrelation q, ?_factiontemplate, creature_template c
			{LEFT JOIN (locales_creature l) ON l.entry=c.entry AND ?}
		WHERE
			q.quest=?d
			AND c.entry=q.id
			AND factiontemplateID=c.faction_A
		',
		($_SESSION['locale']>0)? $_SESSION['locale']: DBSIMPLE_SKIP,
		($_SESSION['locale']>0)? 1: DBSIMPLE_SKIP,
		$quest['entry']
	);
	if($rows)
	{
		foreach($rows as $tmp)
		{
			$tmp['name'] = localizedName($tmp);
			if($tmp['A'] == -1 && $tmp['H'] == 1)
				$tmp['side'] = 'horde';
			elseif($tmp['A'] == 1 && $tmp['H'] == -1)
				$tmp['side'] = 'alliance';
			$quest['start'][] = array_merge($tmp, array('type' => 'npc'));
		}
	}
	unset($rows);

	// ���-���������
	$rows = event_find(array('quest_id' => $quest['entry']));
	if ($rows)
	{
		foreach ($rows as $event)
			foreach ($event['creatures_quests_id'] as $ids)
				if ($ids['quest'] == $quest['entry'])
				{
					$tmp = creatureinfo($ids['creature']);
					if($tmp['react'] == '-1,1')
						$tmp['side'] = 'horde';
					elseif($tmp['react'] == '1,-1')
						$tmp['side'] = 'alliance';
					$tmp['type'] = 'npc';
					$tmp['event'] = $event['entry'];
					$quest['start'][] = $tmp;
				}
	}
	unset($rows);

	// ��
	$rows = $DB->select('
		SELECT g.entry, g.name
			{, l.name_loc?d AS name_loc}
		FROM gameobject_questrelation q, gameobject_template g
			{LEFT JOIN (locales_gameobject l) ON l.entry = g.entry AND ?}
		WHERE
			q.quest=?d
			AND g.entry=q.id
		',
		($_SESSION['locale']>0)? $_SESSION['locale']: DBSIMPLE_SKIP,
		($_SESSION['locale']>0)? 1: DBSIMPLE_SKIP,
		$quest['entry']
	);
	if($rows)
	{
		foreach($rows as $tmp)
		{
			$tmp['name'] = localizedName($tmp);
			$quest['start'][] = @array_merge($tmp, array('type' => 'object'));
		}
	}
	unset($rows);

	// ����
	$rows = $DB->select('
		SELECT i.name, i.entry, i.quality, LOWER(a.iconname) AS iconname
			{, l.name_loc?d AS name_loc}
		FROM ?_icons a, item_template i
			{LEFT JOIN (locales_item l) ON l.entry=i.entry AND ?}
		WHERE
			startquest = ?d
			AND id = displayid
		',
		($_SESSION['locale']>0)? $_SESSION['locale']: DBSIMPLE_SKIP,
		($_SESSION['locale']>0)? 1: DBSIMPLE_SKIP,
		$quest['entry']
	);
	if($rows)
	{
		foreach($rows as $tmp)
		{
			$tmp['name'] = localizedName($tmp);
			$quest['start'][] = @array_merge($tmp, array('type' => 'item'));
		}
	}
	unset($rows);
	
	// ������������
	// ���
	$rows = $DB->select('
		SELECT c.entry, c.name, A, H
			{, l.name_loc?d AS name_loc}
		FROM creature_involvedrelation q, ?_factiontemplate, creature_template c
			{LEFT JOIN (locales_creature l) ON l.entry=c.entry AND ?}
		WHERE
			q.quest=?d
			AND c.entry=q.id
			AND factiontemplateID=c.faction_A
		',
		($_SESSION['locale']>0)? $_SESSION['locale']: DBSIMPLE_SKIP,
		($_SESSION['locale']>0)? 1: DBSIMPLE_SKIP,
		$quest['entry']
	);
	if($rows)
	{
		foreach($rows as $tmp)
		{
			$tmp['name'] = localizedName($tmp);
			if($tmp['A'] == -1 && $tmp['H'] == 1)
				$tmp['side'] = 'horde';
			elseif($tmp['A'] == 1 && $tmp['H'] == -1)
				$tmp['side'] = 'alliance';
			$quest['end'][] = @array_merge($tmp, array('type' => 'npc'));
		}
	}
	unset($rows);

	// ��
	$rows = $DB->select('
		SELECT g.entry, g.name
			{, l.name_loc?d AS name_loc}
		FROM gameobject_involvedrelation q, gameobject_template g
			{LEFT JOIN (locales_gameobject l) ON l.entry = g.entry AND ?}
		WHERE
			q.quest=?d
			AND g.entry=q.id
		',
		($_SESSION['locale']>0)? $_SESSION['locale']: DBSIMPLE_SKIP,
		($_SESSION['locale']>0)? 1: DBSIMPLE_SKIP,
		$quest['entry']
	);
	if($rows)
	{
		foreach($rows as $tmp)
		{
			$tmp['name'] = localizedName($tmp);
			$quest['end'][] = @array_merge($tmp, array('type' => 'object'));
		}
	}
	unset($rows);

	// ���� ��������
	$rows = $DB->select('
			SELECT a.id, a.faction, a.name_loc?d AS name, a.description_loc?d AS description, a.category, a.points, s.iconname, z.areatableID
			FROM ?_spellicons s, ?_achievementcriteria c, ?_achievement a
			LEFT JOIN (?_zones z) ON a.map != -1 AND a.map = z.mapID
			WHERE
				a.icon = s.id
				AND a.id = c.refAchievement
				AND c.type IN (?a)
				AND c.value1 = ?d
			GROUP BY a.id
			ORDER BY a.name_loc?d
		',
		$_SESSION['locale'],
		$_SESSION['locale'],
		array(ACHIEVEMENT_CRITERIA_TYPE_COMPLETE_QUEST),
		$quest['entry'],
		$_SESSION['locale']
	);
	if($rows)
	{
		$quest['criteria_of'] = array();
		foreach($rows as $row)
		{
			allachievementsinfo2($row['id']);
			$quest['criteria_of'][] = achievementinfo2($row);
		}
	}

	// ������� � �������������, ����������� ������
	if ($quest['RewardMailTemplateId'])
	{
		if(!($quest['mailrewards'] = loot('mail_loot_template', $quest['RewardMailTemplateId'])))
			unset ($quest['mailrewards']);
	}
	if ($quest['RewardMailDelay'])
		$quest['maildelay'] = sec_to_time($quest['RewardMailDelay']);

	save_cache(QUEST_PAGE, $cache_key, $quest);
}

global $page;
$page = array(
	'Mapper' => false,
	'Book' => false,
	'Title' => $quest['Title'].' - '.$smarty->get_config_vars('Quests'),
	'tab' => 0,
	'type' => 5,
	'typeid' => $quest['entry'],
	'path' => path(0, 5) // TODO
);

$smarty->assign('page', $page);

// �����������
$smarty->assign('comments', getcomments($page['type'], $page['typeid']));

// ������ � ������
$smarty->assign('quest', $quest);

// ���������� MySQL ��������
$smarty->assign('mysql', $DB->getStatistics());
// ��������� ��������
$smarty->display('quest.tpl');

?>