<?php

class tabledef_NewsTable extends flexDb_TableDefinition {
	public $tablename = 'news';
	public function SetupFields() {
		$this->AddField('news_id',ftNUMBER);
		$this->AddField('time',ftDATE);
		$this->AddField('heading',ftVARCHAR,50);
		$this->AddField('description',ftVARCHAR,150);
		$this->AddField('text',ftTEXT);
		$this->AddField('image',ftIMAGE);
		$this->AddField('archive',ftBOOL);

		$this->SetPrimaryKey('news_id');
	}
}

class module_NewsAdmin extends flexDb_ListDataModule {
	public function SetupParents() {
		$this->AddParent('internalmodule_Admin');
	}
	public function GetTitle() { return 'News List'; }
	public function GetOptions() { return IS_ADMIN | ALLOW_DELETE | ALLOW_FILTER; }
	public function GetTabledef() { return 'tabledef_NewsTable'; }
	public function SetupFields() {
		$this->CreateTable('news');
		$this->AddField('time','time','news','time');
		$this->AddField('heading','heading','news','heading');
		//$this->AddField('text','text','news','text');
		//$this->AddField('image','image','news','image');
	}
	public function ParentLoad($parent) {}
	public function RunModule() {
		$this->ShowData();
	}
}

class module_NewsAdminDetail extends flexDb_SingleDataModule {
	public function SetupParents() {
		$this->AddParent('module_NewsAdmin','news_id','*');
		//breadcrumb::AddModule('module_NewsAdmin');
	}
	public function GetTitle() { return 'Edit News Item'; }
	public function GetOptions() { return IS_ADMIN | ALLOW_DELETE | ALLOW_FILTER | ALLOW_ADD | NO_NAV | ALLOW_EDIT; }
	public function GetTabledef() { return 'tabledef_NewsTable'; }
	public function SetupFields() {
		$this->CreateTable('news');
		$this->AddField('time','time','news','Date',itDATE);
		$this->AddField('heading','heading','news','Heading',itTEXT);
		$this->AddField('description','description','news','Tag Line',itTEXT);
		$this->FieldStyles_Set('description',array('width'=>'60%'));
		$this->AddField('text','text','news','Content',itTEXTAREA);
		$this->FieldStyles_Set('text',array('width'=>'100%','height'=>'15em'));
		$this->AddField('image','image','news','Image',itFILE);
		$this->SetFieldProperty('image','length',150);
		$this->AddField('archive','archive','news','Archive',itCHECKBOX);
	}
	public function ParentLoad($parent) {}
	public function RunModule() {
		$this->ShowData();
	}
}

FlexDB::AddTemplateParser('newsticker','module_NewsTicker::GetOutput','');
class module_NewsTicker extends flexDb_DataModule {
	public function SetupParents() { }
	public function GetTitle() { return ''; }
	public function GetTabledef() { return 'tabledef_NewsTable'; }
	public function SetupFields() {
		$this->CreateTable('news');
		$this->AddField('time','time','news','time');
	//	$this->SetFieldType('time',ftDATE);
		$this->AddField('heading','heading','news','heading');
		$this->AddField('text','text','news','text');
		$this->AddField('description','description','news','text');
		//$this->FieldStyles_Set('text',array('width'=>'100%','height'=>'15em'));
		$this->AddField('image','image','news','image');
		$this->AddOrderBy('`news`.`time`','DESC');
		$this->AddField('archive','archive','news');
		//$this->AddFilter('archive',ctEQ,itNONE,0);
	}
	public function ParentLoad($parent) {}
	static function GetOutput() { ob_start(); CallModuleFunc('module_NewsTicker','RunModule'); $c = ob_get_contents(); ob_end_clean(); return $c; }
	public function RunModule() {
		$rows = $this->GetRows();
		foreach ($rows as $id => $row) {
			if ($row['archive']) unset($rows[$id]);
		}

		$rows = array_slice($rows,0,6);
		module_NewsArchive::ShowRSSLink();
		echo '<table>';
		if (empty($rows))
			echo '<tr><td colspan="0">No news to display.</td></tr>';
		else foreach ($rows as $row) {
			$crop = (strlen($row['text']) > 100) ? substr($row['text'],0,100).'...' : '';
			$link = CallModuleFunc('module_NewsDisplay','GetURL',array('news_id'=>$row['news_id']));
			echo "<tr><td style=\"vertical-align:top;white-space:nowrap;\">{$row['time']}</td><td><a href=\"{$link}\">{$row['heading']}</a><br><span class=\"newsDescription\">{$row['description']}</span></td></tr>";
		}
		echo '<tr><td colspan="2"><a href="'.CallModuleFunc('module_NewsArchive','GetURL').'">News Archive</a></td></tr>';
		echo '</table>';

		//FlexDB::AppendVar('head','<link rel="alternate" type="application/rss+xml" title="'.FlexDB::GetDomainName().' News Feed" href="'.$this->GetURL(array('__ajax'=>'getNewsRSS')).'" />');
//		FlexDB::AppendVar('head','<link rel="alternate" type="application/atom+xml" title="'.FlexDB::GetDomainName().' News Feed" href="'.$this->GetURL(array('__ajax'=>'getNewsRSS')).'" />');
	}

//	public function GetRows() {
//		$ds = $this->GetDataset();
//		return GetRows($ds);
//	}
}


class module_NewsArchive extends flexDb_DataModule {
	public function SetupParents() {
		$this->RegisterAjax('getNewsRSS',array($this,'getRSS'));
		$this->SetRewrite(true);
	}
	public function GetUUID() { return 'news-archive'; }
	public function GetTitle() { return 'News &amp; Articles'; }
	public function GetTabledef() { return 'tabledef_NewsTable'; }
	public function SetupFields() {
		$this->CreateTable('news');
		$this->AddField('time','time','news','time');
	//	$this->SetFieldType('time',ftDATE);
		$this->AddField('heading','heading','news','heading');
		$this->AddField('text','text','news','text');
		$this->AddField('description','description','news','text');
		//$this->FieldStyles_Set('text',array('width'=>'100%','height'=>'15em'));
		$this->AddField('image','image','news','image');
		$this->AddOrderBy('`news`.`time`','DESC');
		$this->AddField('archive','archive','news');
		//$this->AddFilter('archive',ctEQ,itNONE,0);

		$this->AddOrderBy('time');
	}
	static $rssShown = false;
	public static function ShowRSSLink() {
		if (self::$rssShown) return;
		FlexDB::AppendVar('</head>','<link rel="alternate" type="application/atom+xml" title="'.FlexDB::GetDomainName().' News Feed" href="'.CallModuleFunc(__CLASS__,'GetURL',array('__ajax'=>'getNewsRSS')).'" />'."\n");
		self::$rssShown = true;
	}
	public function ParentLoad($parent) {}
	public function RunModule() {
		self::ShowRSSLink();
		echo '<h1>News &amp; Articles</h1>';
		$ds = $this->GetDataset();
		$rows = GetRows($ds);

		$month = NULL;
		echo '<table class="newsTable">';
		foreach ($rows as $row) {
			$time = strtotime($row['time']);
			$currMonth = date('F Y',$time);
			$day = date('jS F Y',$time);
			if ($month != $currMonth) {
				$month = $currMonth;
				echo '<tr><td colspan="2"><h2>'.$currMonth.'</h2></td></tr>';
			}
			$url = CallModuleFunc('module_NewsDisplay','GetURL',$row['news_id']);
			echo '<tr><td style="padding-right:1em">'.$day.'</td><td><span class="newsHeading"><a href="'.$url.'">'.$row['heading'].'</a></span><br><span class="newsDescription">'.$row['description'].'</span></td></tr>';
		}
		echo '</table>';
	}

	public function getRSS() {
		$dom = FlexDB::GetDomainName();
		$date = date('r');

		$rows = $this->GetRows();
		$items = '';
		foreach ($rows as $row) {
			$crop = (strlen($row['text']) > 100) ? substr($row['text'],0,100).'...' : '';
			$link = htmlentities('http://'.$dom.CallModuleFunc('module_NewsDisplay','GetURL',array('news_id'=>$row['news_id'])));
			$img = '';
			if ($row['image']) $img = "\n".'  <media:thumbnail width="150" height="150" url="'.htmlentities('http://'.$dom.$this->GetImageLinkFromTable('image','news','news_id',$row['news_id'],150)).'"/>';
			$pubDate = date('r',strtotime($row['time']));
			$items .= <<<FIN
 <item>
  <title>{$row['heading']}</title>
  <description>{$row['description']}</description>
  <link>{$link}</link>
  <guid isPermaLink="false">{$link}</guid>
  <pubDate>{$pubDate}</pubDate>{$img}
 </item>
FIN;
		}

		header('Content-Type: application/rss+xml',true);
		$self = htmlentities('http://'.$dom.$_SERVER['REQUEST_URI']);
		echo <<<FIN
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom"><channel>
 <atom:link href="{$self}" rel="self" type="application/rss+xml" />
 <title>{$dom} News Feed</title>
 <description>Latest news from {$dom}</description>
 <link>http://{$dom}</link>
 <lastBuildDate>{$date}</lastBuildDate>
 <language>en-gb</language>
 <ttl>15</ttl>
{$items}
</channel></rss>
FIN;
		/*
 <link></link>
 <description></description>
 <copyright></copyright>
 <image>
  <title>BBC News</title>
  <url>http://news.bbc.co.uk/nol/shared/img/bbc_news_120x60.gif</url>
  <link>http://news.bbc.co.uk/go/rss/-/1/hi/uk/default.stm</link>
 </image>*/
	}
//	public function GetRows() {
//		$ds = $this->GetDataset();
//		return GetRows($ds);
//	}
}

class module_NewsDisplay extends flexDb_DataModule {
	public function SetupParents() {
		$this->AddParent('module_NewsTicker','news_id','*');
		$this->SetRewrite(array('{heading}','{news_id}'),true);

		//breadcrumb::AddModule('module_NewsArchive');
	}
	public function GetTabledef() { return 'tabledef_NewsTable'; }
	public function SetupFields() {
		$this->CreateTable('news');
		$this->AddField('time','time','news','time');
		$this->SetFieldType('time',ftDATE);
		$this->AddField('heading','heading','news','heading');
		$this->AddField('text','text','news','text');
		$this->FieldStyles_Set('text',array('width'=>'100%','height'=>'15em'));
		$this->AddField('image','image','news','image');
	}
	public function GetUUID() { return 'news'; }
	public function ParentLoad($parent) {}
	public function RunModule() {
		uBreadcrumb::AddCrumb('News &amp; Articles',CallModuleFunc('module_NewsArchive', 'GetURL'));
		$ds = $this->GetDataset();
		$rec = $this->GetRecord($ds,0);
		FlexDB::SetTitle($rec['heading']);
		$img = '';
		if ($rec['image']) {
			$imgLink = $this->GetImageLinkFromTable('image','news','news_id',$rec['news_id'],300);
			$img = '<img src="'.$imgLink.'" style="float:right;margin-right:30px;margin-top:30px">';
		}
		$text = nl2br($rec['text']);
		echo <<<FIN
$img
<h1>{$rec['heading']}</h1>
<p>{$rec['time']}</p>
<p>$text</p>
FIN;
	}
}

?>