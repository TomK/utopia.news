<?php

class tabledef_NewsTable extends uTableDef {
	public $tablename = 'news';
	public function SetupFields() {
		$this->AddField('news_id',ftNUMBER);
		$this->AddField('time',ftDATE);
		$this->AddField('heading',ftVARCHAR,50);
		$this->AddField('description',ftVARCHAR,150);
		$this->AddField('tags',ftTEXT);
		$this->AddField('text',ftTEXT);
		$this->AddField('image',ftIMAGE);
		$this->AddField('archive',ftBOOL);

		$this->SetPrimaryKey('news_id');
	}
}

class module_NewsAdmin extends uListDataModule implements iAdminModule {
	public function SetupParents() {
		$this->AddParent('');
	}
	public function GetTitle() { return 'News List'; }
	public function GetOptions() { return ALLOW_DELETE | ALLOW_FILTER; }
	public function GetTabledef() { return 'tabledef_NewsTable'; }
	public function SetupFields() {
		$this->CreateTable('news');
		$this->AddField('time','time','news','Posted');
		$this->AddField('heading','heading','news','Title');
		
		$this->AddFilter('time',ctGTEQ,itDATE);
		$this->AddFilter('time',ctLTEQ,itDATE);
		$this->AddFilter('heading',ctLIKE,itTEXT);
	}
	public function RunModule() {
		$this->ShowData();
	}
}

class module_NewsAdminDetail extends uSingleDataModule implements iAdminModule {
	public function SetupParents() {
		$this->AddParent('module_NewsAdmin','news_id','*');
		$this->AddParent('module_NewsAdmin');
		//breadcrumb::AddModule('module_NewsAdmin');
	}
	public function GetTitle() { return 'Edit News Item'; }
	public function GetOptions() { return ALLOW_DELETE | ALLOW_FILTER | ALLOW_ADD | NO_NAV | ALLOW_EDIT; }
	public function GetTabledef() { return 'tabledef_NewsTable'; }
	public function SetupFields() {
		$this->CreateTable('news');
		$this->AddField('time','time','news','Post Date',itDATE);
		$this->AddField('heading','heading','news','Title',itTEXT);
		$this->AddField('description','description','news','Description',itTEXT);
		$this->FieldStyles_Set('description',array('width'=>'60%'));
		$this->AddField('tags','tags','news','Tags',itTEXT);
		$this->FieldStyles_Set('tags',array('width'=>'60%'));
		$this->AddField('text','text','news','Content',itHTML);
		$this->FieldStyles_Set('text',array('width'=>'100%','height'=>'10em'));
		$this->AddField('curr_image','image','news','Current Image');
		$this->FieldStyles_Set('curr_image',array('height'=>100));
		$this->AddField('image','image','news','Image',itFILE);
		$this->AddField('archive','archive','news','Archive',itCHECKBOX);
	}
	public function RunModule() {
		$this->ShowData();
	}
}

class module_NewsRSS extends uDataModule {
	public function SetupParents() { 
		$this->SetRewrite(true);
	}

	public function GetUUID() { return 'news-rss'; }
	public function GetTitle() { return 'News RSS'; }
	public function GetOptions() { return ALLOW_FILTER; }
	public function GetTabledef() { return 'tabledef_NewsTable'; }
	public function SetupFields() {
		$this->CreateTable('news');
		$this->AddField('time','time','news','Date',itDATE);
		$this->AddField('heading','heading','news','Heading',itTEXT);
		$this->AddField('description','description','news','Description',itTEXT);
		$this->AddField('tags','tags','news','Tags',itTEXT);
		$this->AddField('text','text','news','Content',itHTML);
		$this->AddField('image','image','news','Image',itFILE);
		$this->AddField('archive','archive','news','Archive',itCHECKBOX);
		$this->AddOrderBy('time','desc');
	}
	public function RunModule() {
		utopia::CancelTemplate();
		$dom = utopia::GetDomainName();

		$rows = $this->GetRows();
		$items = '';
		$obj = utopia::GetInstance('module_NewsDisplay');
		$pubDate = null;
		foreach ($rows as $row) {
			$crop = (strlen($row['text']) > 100) ? substr($row['text'],0,100).'...' : '';
			$link = htmlentities('http://'.$dom.$obj->GetURL(array('news_id'=>$row['news_id'])));
			$img = '';
			if ($row['image']) $img = "\n".'  <media:thumbnail width="150" height="150" url="'.htmlentities('http://'.$dom.$this->GetImageLinkFromTable('image','news','news_id',$row['news_id'],150)).'"/>';
			$updated = date('r',strtotime($row['time']));
			if (!$pubDate || (strtotime($row['time']) > $pubDate)) $pubDate = strtotime($row['time']);
			$items .= <<<FIN
 <item>
  <title>{$row['heading']}</title>
  <description>{$row['description']}</description>
  <link>{$link}</link>
  <guid isPermaLink="false">{$link}</guid>
  <pubDate>{$updated}</pubDate>{$img}
 </item>
FIN;
		}
		$pubDate = date('r',$pubDate);

		header('Content-Type: application/rss+xml',true);
		$self = htmlentities('http://'.$dom.$_SERVER['REQUEST_URI']);
		echo <<<FIN
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom"><channel>
 <atom:link href="{$self}" rel="self" type="application/rss+xml" />
 <title>{$dom} News Feed</title>
 <description>Latest news from {$dom}</description>
 <link>http://{$dom}</link>
 <lastBuildDate>{$pubDate}</lastBuildDate>
 <language>en-gb</language>
 <ttl>15</ttl>
{$items}
</channel></rss>
FIN;
	}
}

class module_NewsDisplay extends uDataModule {
	public function SetupParents() {
		$this->SetRewrite('{heading}-{news_id}',true);
		modOpts::AddOption('news_per_page','News Articles Per Page','News',2);
		modOpts::AddOption('news_widget_archive','News Archive Widget','News','news-archive');
		modOpts::AddOption('news_widget_article','News Article Widget','News','news-article');
		
		uSearch::AddSearchRecipient(__CLASS__,array('heading','text'),'heading','text');
	}
	public function GetTabledef() { return 'tabledef_NewsTable'; }
	public function SetupFields() {
		$this->CreateTable('news');
		$this->AddField('time','time','news','time');
		$this->SetFieldType('time',ftDATE);
		$this->AddField('heading','heading','news','heading');
		$this->AddField('text','text','news','text');
		$this->AddField('description','description','news','description');
		$this->AddField('image','image','news','image');
		$this->AddFilter('news_id',ctEQ);
		
		$this->AddOrderBy('time');
	}
	public function GetUUID() { return 'news'; }
	public function RunModule() {
		if (isset($_GET['news_id'])) {
			$rec = $this->LookupRecord();
			if (!$rec) utopia::PageNotFound();
			utopia::SetTitle($rec['heading']);
			utopia::SetDescription($rec['description']);

			echo '{widget.'.modOpts::GetOption('news_widget_article').'}';
			return;
		}
		utopia::SetTitle('News Archive');
		echo '{widget.'.modOpts::GetOption('news_widget_archive').'}';
		uEvents::AddCallback('ProcessDomDocument',array($this,'AddRssLink'));
	}
	public function AddRssLink($o,$e,$doc) {
		$rssobj = utopia::GetInstance('module_NewsRSS');
		$link = $doc->createElement('link');
		$link->setAttribute('rel','alternate');
		$link->setAttribute('type','application/atom+xml');
		$link->setAttribute('title',utopia::GetDomainName().' News Feed');
		$link->setAttribute('href',$rssobj->GetURL());

		$head = $doc->getElementsByTagName('head')->item(0);
		$head->appendChild($link);
	}
}
