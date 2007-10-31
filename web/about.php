<?
// about.php:
// Main page of YCML.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: about.php,v 1.17 2007-10-31 17:15:52 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/constituent.php';
require_once '../phplib/recent.php';

page_header();
if (OPTION_AREA_TYPE == 'WMC') 
    mp_about_page();
else
    cllr_about_page();
page_footer();

function mp_about_page() {
    print recent_messages();
    print recent_replies();
    $num = db_getOne("select count(distinct area_id) from message where state='approved'");
?>
<div id="indented">

<p><em>&ldquo;So, the voting is over. The politicians vanish to Westminster, and
everything carries on as before, right?&rdquo;</em></p>

<p>Wrong. Between elections the internet is really starting to
challenge politics as usual. As part of this change, we'd like to put
you in touch with your MP. Not for a specific purpose, but in
order to hear what they're working on, to debate their thoughts in a
safe, friendly environment, and generally to build better, more useful
relationships between constituents and their MPs. That's why we built
HearFromYourMP.</p>

<dl>
<dt>What is HearFromYourMP?
<dd>HearFromYourMP is a site which allows you, the constituent, to
sign up to get emails from your local MP about local issues. When your
MP writes to you and other constituents, we give you the chance to
discuss what has been said in a simple online forum.

<dt>Why should I sign up?
<dd>If you care about your local community, environment, businesses,
roads, schools, parking arrangements, hedgehog tunnels, hop-scotch
courts, or anything, HearFromYourMP is for you. HearFromYourMP is
about building better, more constructive long term relationships
between representatives and the people they represent. You can find
out more about the important issues in your area, and give valuable
democratic feedback to the most powerful politician in your area.

<dt>Okay, so how does it work?
<dd>If you enter your details, we'll add you to a queue of other
people in your constituency. When enough have signed up, your MP will
get sent an email. It'll say &ldquo;25 of your constituents would like to
hear what you're up to. Click here to let them know&rdquo;. If they
don't respond, nothing will happen, until your MP gets a further email
which says there are now 50, then 75, 100, 150 &ndash; until it is
nonsensical not to reply and start talking.</p>

<p>When your MP sends you mail it won't be one-way spam, and it won't be
an inbox-filling free-for-all. Instead, each email comes with a link
at the bottom, which takes you straight to a web page containing a
copy of the email your MP wrote, along with any comments by other
constituents. To leave your thoughts, you just enter your text and hit
enter. There's no tiresome login &ndash; you can just start talking about
what they've said. Safe, easy and democratic.</p>

<p align="center"><strong>Sign up now
<?  if ($num>1)
        echo ' &ndash; ', $num, ' MPs have already sent out messages.';
    constituent_subscribe_box();
?>
</strong></p>

<dt>I am an MP, or work in an MP's office. How do I take part?
<dd><a href="/contact/">Contact us</a> saying you'd like to send
a message to your constituents, and we'll tell you what to do
If you'd like more information first, read our <a
href="/about-mps">MPs' Frequently Asked Questions page</a>.

<dt>Will it fill my inbox with ranting rubbish from other constituents?
<dd>No &mdash; only the MP can send messages to all list subscribers. If they
choose to send rubbish, you can either unsubscribe, or have a full and
frank discussion about why they shouldn't send faceless propaganda on
the discussion thread that comes attached to each email.

<dt>How does this discussion forum work then? Where is it?
<dd>The forum does not exist until your MP sends their first message. This
message contains a link at the bottom which is unique to each user.
When you click on it it logs you in to a new HearFromYourMP
discussion thread. The first post in the thread contains the MP's
email. You can then read what other people have been saying, or leave
a comment yourself.

<dt>How are you going to stop the forums descending into ranting, partisan nonsense?
<dd>We have various approaches to this. First, we have a strict moderation
policy &mdash; discussion must be constructive, non-partisan and friendly,
or we'll have no compunction about removing posts. Secondly, users are
limited to two forum posts per day, a method pioneered by Steve Clift
at e-democracy.org, and proven to keep people behaving themselves.

<dt>What makes you think MPs will ever post?
<dd>Some MPs will post immediately and with enthusiasm. Many more will be
uncertain, and so we will provide encouragement. As people sign up,
each MP will be mailed whenever their mailing lists get to 25, 50,
100, 200, 300, 500 etc. etc. They won't be harrassed &mdash; they'll never
get more than one mail a week no matter how fast people sign up.
Eventually we believe that the mailing lists will grow naturally to a
size where it is nonsensical for MPs not to engage.

<dt>Does the mailing list belong to the MP? What happens if my MP changes?
<dd>There is one list per constituency, not per MP, and we will continue to
accept subscribers regardless of whether your current MP chooses to use the
site or not. If your MP changes for any reason, we will hand access to the
list over to their successor. If your constituency boundaries change, we will
use your postcode to calculate your new constituency, and move you to the
right list. We will not disclose your email address to the MP,
unless you write to them.

<dt>How much of the UK does HearFromYourMP cover?
<dd>All of it. All of Blighty. Gor bless 'er. Brings a tear to your eye.

<dt>Can I link to you from my website?
<dd>Yes, and we provide <a href="link">special code</a> to make it easy for you.

<dt>Who are you?
<dd>This site was built by <a href="http://www.mysociety.org/">mySociety</a>. mySociety is a charitable
organisation which has grown out of the community of volunteers who
built sites like TheyWorkForYou.com. mySociety's primary mission is to
build internet projects which give people simple, tangible benefits in
the civic and community aspects of their lives. Our first project was
<a href="http://www.writetothem.com/">WriteToThem</a>, where you can write to any of your elected
representatives, for free. Our more recent sites include
<a href="http://www.pledgebank.com/">PledgeBank</a> and
<a href="http://www.fixmystreet.com/">FixMyStreet</a>.

<dt>Who pays for it?
<dd>HearFromYourMP has been built by mySociety thanks to the effort of a
combination of paid core developers and unpaid volunteers. The core
developers were paid for by the ODPM's e-innovations fund in
partnership with West Sussex County Council. Our servers are kindly
hosted by <a href="http://www.easynet.net/publicsector/">Easynet</a>.

<dt>Do you need any help with the project?
<dd>Yes, we can use help in all sorts of ways, technical or non-technical.
Please <a href="/contact/">contact us</a> if you want to get in touch.

<dt>What are the terms and conditions of usage?
<dd>We've got a <a href="/terms">proper Ts&amp;Cs page</a>.
The main rule is simple though &mdash;
don't be an arse and you'll get by fine.

<dt>Are you going to send me evil spam?
<dd>No, you'll only ever get mail from your MP, plus perhaps the rare
email asking whether you want to make use of new services within
HearFromYourMP (we are thinking of adding councillors, you see).
It goes without saying that we'd never give or sell your email
addresses to anyone else.

<dt>Is this open source?
<dd>Yep, all our code is open source under the Affero GPL. You can
<a href="https://secure.mysociety.org/cvstrac/dir?d=mysociety">find
it here</a> (in the 'ycml' directory). We use some
licensed data to do postcode and constituency lookups though, I'm
afraid. If you are interested in translating it to another country,
we'll be glad to do what we can to help.

<dt>I want to contact someone in charge, or request a new/different feature &mdash;
what should I do?
<dd>Write to us at <a href="mailto:team&#64;hearfromyourmp.com">team&#64;hearfromyourmp.com</a>,
tell us what you want, or build it yourself as a volunteer. :)

<dt>You guys are so cool &mdash; how can we thank you?
<dd>Gin and chocolates, marked c/o Tom Steinberg, 18 Victoria Park
Square, London E2 9PF. I'll make sure the rest of the gang get their
share.  Honest. (Or, if you are unaccountably out of alcohol and sweets,
you can <a href="https://secure.mysociety.org/donate/">donate money</a>
to the charity that runs HearFromYourMP.)

<dt>What's that background image on every page?
<dd>It's a patent illustration for a lever voting machine. As 
<a href="http://www.cs.uiowa.edu/~jones/voting/pictures/#lever">Douglas W. Jones's Illustrated Voting Machine History</a> says:
<blockquote>
<img align="right" src="/shoupsm.png" alt="">&quot;Two manufacturers split the market for lever voting machines, Shoup and AVM (Automatic Voting Machines); the latter company is the direct descendant of Jacob H. Myers original company, organized in 1895. Ransom F. Shoup made a number of improvements to lever voting machines between 1929 and 1975. [The image] shows an early Shoup machine; like most of its successors, this included a substantial voting booth, yet it could be collapsed into a package that was relatively easy to transport and store.&quot;
</blockquote>

</dl>
</div>
<?
}

function cllr_about_page() {
    print recent_messages();
    print recent_replies();
    $num = db_getOne("select count(distinct area_id) from message where state='approved'");
?>
<div id="indented">

<p><em>&ldquo;So, the voting is over. The politicians vanish to their town halls, and
everything carries on as before, right?&rdquo;</em></p>

<p>Wrong. Between elections the internet is really starting to
challenge politics as usual. As part of this change, we'd like to put
you in touch with your councillor. Not for a specific purpose, but in
order to hear what they're working on, to debate their thoughts in a
safe, friendly environment, and generally to build better, more useful
relationships between constituents and their councillors. That's why we built
HearFromYourCouncillor.</p>

<dl>
<dt>What is HearFromYourCouncillor?
<dd>HearFromYourCouncillor is a site which allows you, the constituent, to
sign up to get emails from your local councillors about local issues. When your
councillors write to you and other constituents, we give you the chance to
discuss what has been said in a simple online forum.

<dt>Why should I sign up?
<dd>If you care about your local community, environment, businesses,
roads, schools, parking arrangements, hedgehog tunnels, hop-scotch
courts, or anything, HearFromYourCouncillor is for you. HearFromYourCouncillor is
about building better, more constructive long term relationships
between representatives and the people they represent. You can find
out more about the important issues in your area, and give valuable
democratic feedback to the most powerful politician in your area.

<dt>Okay, so how does it work?
<dd>If you enter your details, we'll add you to a queue of other
people in your ward. When enough have signed up, your councillors will
get sent an email. It'll say &ldquo;5 of your constituents would like to
hear what you're up to. Click here to let them know&rdquo;. If they
don't respond, nothing will happen, until they get a further email
which says there are now 10, then 15, 20, 30 &ndash; until it is
nonsensical not to reply and start talking.</p>

<p>When your councillors send you mail it won't be one-way spam, and it won't be
an inbox-filling free-for-all. Instead, each email comes with a link
at the bottom, which takes you straight to a web page containing a
copy of the email your councillor wrote, along with any comments by other
constituents. To leave your thoughts, you just enter your text and hit
enter. There's no tiresome login &ndash; you can just start talking about
what they've said. Safe, easy and democratic.</p>

<p align="center"><strong>Sign up now
<?  if ($num>1)
        echo ' &ndash; ', $num, ' councillors have already sent out messages.';
    constituent_subscribe_box();
?>
</strong></p>

<dt>Will it fill my inbox with ranting rubbish from other constituents?
<dd>No &mdash; only the councillors can send messages to all list subscribers. If they
choose to send rubbish, you can either unsubscribe, or have a full and
frank discussion about why they shouldn't send faceless propaganda on
the discussion thread that comes attached to each email.

<dt>How does this discussion forum work then? Where is it?
<dd>The forum does not exist until your councillor sends their first message. This
message contains a link at the bottom which is unique to each user.
When you click on it it logs you in to a new HearFromYourCouncillor
discussion thread. The first post in the thread contains the councillor's
email. You can then read what other people have been saying, or leave
a comment yourself.

<dt>How are you going to stop the forums descending into ranting, partisan nonsense?
<dd>We have various approaches to this. First, we have a strict moderation
policy &mdash; discussion must be constructive, non-partisan and friendly,
or we'll have no compunction about removing posts. Secondly, users are
limited to two forum posts per day, a method pioneered by Steve Clift
at e-democracy.org, and proven to keep people behaving themselves.

<dt>What makes you think councillors will ever post?
<dd>Some councillors will post immediately and with enthusiasm. Many more will be
uncertain, and so we will provide encouragement. As people sign up,
each councillor will be mailed whenever their mailing lists get to 5, 10,
20, 30, 40, 50 etc. etc. They won't be harrassed &mdash; they'll never
get more than one mail a week no matter how fast people sign up.
Eventually we believe that the mailing lists will grow naturally to a
size where it is nonsensical for councillors not to engage.

<dt>Does the mailing list belong to the councillor? What happens if my councillor changes?
<dd>There is one list per ward, not per councillor, and we will continue to
accept subscribers regardless of whether your current councillor chooses to use the
site or not. If your councillor changes for any reason, we will hand access to the
list over to their successor. If your constituency boundaries change, we will
use your postcode to calculate your new constituency, and move you to the
right list. We will not disclose your email address to the councillors,
unless you write to them.

<dt>How much of the UK does HearFromYourCouncillor cover?
<dd>Currently we cover Cheltenham Borough Council.

<dt>Can I link to you from my website?
<dd>Yes, and we provide <a href="link">special code</a> to make it easy for you.

<dt>Who are you?
<dd>This site was built by <a href="http://www.mysociety.org/">mySociety</a>. mySociety is a charitable
organisation which has grown out of the community of volunteers who
built sites like TheyWorkForYou.com. mySociety's primary mission is to
build internet projects which give people simple, tangible benefits in
the civic and community aspects of their lives. Our first project was
<a href="http://www.writetothem.com/">WriteToThem</a>, where you can write to any of your elected
representatives, for free. Our more recent sites include
<a href="http://www.pledgebank.com/">PledgeBank</a> and
<a href="http://www.fixmystreet.com/">FixMyStreet</a>.

<dt>Who pays for it?
<dd>HearFromYourCouncillor has been built by mySociety thanks to the effort of a
combination of paid core developers and unpaid volunteers. The core
developers were paid for by the ODPM's e-innovations fund in
partnership with West Sussex County Council. Our servers are kindly
hosted by <a href="http://www.easynet.net/publicsector/">Easynet</a>.

<dt>Do you need any help with the project?
<dd>Yes, we can use help in all sorts of ways, technical or non-technical.
Please <a href="/contact/">contact us</a> if you want to get in touch.

<dt>What are the terms and conditions of usage?
<dd>We've got a <a href="/terms">proper Ts&amp;Cs page</a>.
The main rule is simple though &mdash;
don't be an arse and you'll get by fine.

<dt>Are you going to send me evil spam?
<dd>No, you'll only ever get mail from your councillor, plus perhaps the rare
email asking whether you want to make use of new services within
HearFromYourCouncillor.
It goes without saying that we'd never give or sell your email
addresses to anyone else.

<dt>Is this open source?
<dd>Yep, all our code is open source under the Affero GPL. You can
<a href="https://secure.mysociety.org/cvstrac/dir?d=mysociety">find
it here</a> (in the 'ycml' directory). We use some
licensed data to do postcode and constituency lookups though, I'm
afraid. If you are interested in translating it to another country,
we'll be glad to do what we can to help.

<dt>I want to contact someone in charge, or request a new/different feature &mdash;
what should I do?
<dd>Write to us at <a href="mailto:team&#64;hearfromyourmp.com">team&#64;hearfromyourmp.com</a>,
tell us what you want, or build it yourself as a volunteer. :)

<dt>You guys are so cool &mdash; how can we thank you?
<dd>Gin and chocolates, marked c/o Tom Steinberg, 18 Victoria Park
Square, London E2 9PF. I'll make sure the rest of the gang get their
share.  Honest. (Or, if you are unaccountably out of alcohol and sweets,
you can <a href="https://secure.mysociety.org/donate/">donate money</a>
to the charity that runs HearFromYourCouncillor.)

<dt>What's that background image on every page?
<dd>It's a patent illustration for a lever voting machine. As 
<a href="http://www.cs.uiowa.edu/~jones/voting/pictures/#lever">Douglas W. Jones's Illustrated Voting Machine History</a> says:
<blockquote>
<img align="right" src="/shoupsm.png" alt="">&quot;Two manufacturers split the market for lever voting machines, Shoup and AVM (Automatic Voting Machines); the latter company is the direct descendant of Jacob H. Myers original company, organized in 1895. Ransom F. Shoup made a number of improvements to lever voting machines between 1929 and 1975. [The image] shows an early Shoup machine; like most of its successors, this included a substantial voting booth, yet it could be collapsed into a package that was relatively easy to transport and store.&quot;
</blockquote>

</dl>
</div>
<?
}


