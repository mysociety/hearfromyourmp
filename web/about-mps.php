<?
// about-mps.php:
// About page geared towards MPs with questions.
//
// Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: about-mps.php,v 1.3 2007-10-31 17:15:52 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/recent.php';

page_header();
about_mps_page();
page_footer();

function about_mps_page() {
    print recent_messages();
    print recent_replies();
?>

<div id="indented">

<h2>MPs&rsquo; Frequently Asked Questions</h2>

<p>This page explains to MPs what HearFromYourMP is,
and why you might want to use it. For the general point of view, 
see <a href="/about">about this site</a> instead.

<h3>Overview</h3>

<dl>

<dt id="what">So, what is this &lsquo;HearFromYourMP&rsquo; thing?</dt>

<dd>HearFromYourMP is a website that allows constituents to sign up to
get emails from their local MP. MPs submit messages, which are then
sent out by email to the people who have signed up in their
constituency. The messages are also displayed on the website. The
constituents who've signed up are then able to post comments on the
message via the website.</dd>

<dt id="cost">Is it free to use?</dt>

<dd>Yes. It's completely free for both MPs and constituents to use.</dd>

<dt id="write">Can't my constituents just write to me with their
concerns?</dt>

<dd>We've got a separate website, called <a
href="http://www.writetothem.com">WriteToThem</a>, for that.

HearFromYourMP is different. It is a chance for you to set the
agenda, tell your constituents what you are working on and
are concerned about, and get feedback about what they think.

</dd>


<dt id="who">So who runs all this, and what's in it for them?</dt>

<dd>This site was built by <a
href="http://mysociety.org/">mySociety</a>. mySociety is a charitable
organisation which has grown out of the community of volunteers who
built sites like 
<a href="http://www.theyworkforyou.com">TheyWorkForYou.com</a>. 
mySociety's primary mission is to build internet projects which give
people simple, tangible benefits in the civic and community aspects of
their lives.</dd>

</dl>

<h3>Benefits of Using HearFromYourMP</h3>

<dl>

<dt id="why">Why should I use HearFromYourMP?</dt>

<dd>As an elected representative, it's important that those
constituents who want to follow your actions and efforts on their
behalf are able to. It's also useful for you to be able to be able to
receive direct feedback from them. HearFromYourMP provides an
easy-to-use solution to both of these issues.</dd>

<dt id="mailinglist">What makes HearFromYourMP better than a simple
email mailing list, an online forum, or a blog?</dt>

<dd>Lots of reasons!
<ul>
<li>There's no set-up or on-going administration for you &mdash;
the system is already set up and ready to use. Registrations and
removal requests are all handled automatically.</li>
<li>The opportunity for constituents to post their own
comments makes this system more attractive to constituents, allowing
your messages to reach a wider audience.</li>
<li>Unlike a blog or a forum, we take steps to make sure only your
constituents can post comments. Rather than becoming a national
(or international!) debating forum, it is a space for you to talk with
your constituents.</li>
<li>The posting format and restrictions mean that there's less risk of
the junk or abusive posts that often plague traditional forum setups
(see also the <a href="#abuse" title="Won't there be problems with
abusive or irrelevent replies to my messages?">Won't there be problems
&hellip;</a> question below).</li>
<li>This is a neutral space run by an independent group with a proven
track record. Both you and your constituents can feel comfortable
signing up, confident that unsubscription requests will be honoured,
and that your email addresses will not be given out to third
parties.</li>

</ul>
</dd>
</dl>

<h3>How it all Works</h3>

<dl>

<dt>So how does it work in detail?</dt>

<dd><ul>

<li>People sign up by registering their email address and postcode,
using the online form at <a
href="http://www.hearfromyourmp.com/">www.hearfromyourmp.com</a></li>

<li>MPs are sent occasional emails encouraging them to take part.
The first of these is when 25 constituents have signed up.
</li>

<li>MP decides to take part.</li>

<li>MP sends message by following the link in the email and typing in
their message.</li>

<li>The message is sent by email to the people who've signed up, and
is also displayed online.</li>

<li>The email contains a special link which allows those receiving it
to post comments next to the online version.</li>

</ul></dd>

<dt id="messages">What sort of messages can / should I send through
the system?</dt>

<dd>It's basically up to you. Remember, however, that your messages
will be viewed by the constituents that have signed up, and also
available online for anyone else who's interested to read. People will
probably be more interested to read personal details, commentary and
plans directly from the MP, rather than something that reads like a
press release or a party political broadcast.</dd>

<dt id="abuse">Won't there be problems with abusive or irrelevant replies to my messages?</dt>

<dd>Hopefully not. With any system that allows comments from the
general public, there is a risk that it will be abused. However, the
registration system, the fact that you need to register in advance of
a message to post a comment on it, and the fact that comments are
limited to two per person per day all help to prevent abuse. In
practice, abusive or irrelevant posts have not been a problem.
If any appear they are simply reported and easily removed.</dd>

<dt>Can I see an example of HearFromYourMP in action?</dt>

<dd>Yes. The online view of messages and comments can be seen for the
constituencies with <a
href="http://www.hearfromyourmp.com/league?s=m">MPs who are already
using the system</a>.</dd>

<h3>Problems and Switching</h3>

<dt>I'm not really into computers and the internet. Will this be a
problem?</dt>

<dd>It shouldn't be. The system is simple to use, and requires no
specialist or technical knowledge. All you need is a web browser and
something to say.</dd>

<dt id="signups">Does it matter how many of my constituents have
signed up?</dt>

<dd>Not at all. You can start sending messages with only a couple of
people signed up, or refuse to take part when there are
thousands. Obviously, if not many people have signed up, your messages
won't be reaching many. On the other hand, ignoring the wishes of
hundreds of your constituents to hear from you may have an adverse
effect at the next general election.</dd>

<dt id="number">Can I see how many people have signed up for my
constituency?</dt>

<dd>Yes. Just look for your constituency in the <a
href="http://www.hearfromyourmp.com/league?s=c">league table</a>.</dd>

<dt>I already run a mailing list. Can I add those people who've signed up to HearFromYourMP to my own list?</dt>

<dd>Unfortunately not. As part of our <a
href="http://www.hearfromyourmp.com/privacy">privacy policy</a>, we
promise not to divulge the details of the people who sign up to anyone
else. This includes the MPs themselves. You are of course free to use
HearFromYourMP to send out copies of the messages that go your your
existing list, or to send a one-time message containing instructions
on how to sign up to your own list. (See also <a
href="#mailinglist">What makes it better than a simple email mailing
list?</a> above, and <a href="#remove">Can you remove my name to stop
people signing up?</a> below.)</dd>

<dt>I already run a mailing list. If I switch to using HearFromYourMP, could I automatically sign up all the people on my list?</dt>

<dd>Unfortunately not, because we need to check their postcodes. 
If you wish to switch completely, you'll need to encourage your
existing list members to sign-up at <a
href="http://www.hearfromyourmp.com/">www.hearfromyourmp.com</a>. 
To encourage them to do so there's a page of information at <a
href="http://www.hearfromyourmp.com/about">www.hearfromyourmp.com/about</a>.
Since people may not respond immediately, you can always continue to
use your existing mailing list to send out a copy of any messages sent
through HearFromYourMP.</dd>

</dl>


<h3>Next Steps</h3>

<dl>

<dt id="convinced">Ok, I'm convinced, and I'd like to take part. What
do I need to do?</dt>

<dd>If you've reached a threshold and received an email from us,
simply follow its instructions. If you haven't had an email, or can't
find it, simply email us at 
<a href="mailto:team@hearfromyourmp.com">team@hearfromyourmp.com</a>
and we'll be happy to help.</dd>

<dt id="unsure">I'm not sure, and would like more information. Whom
should I contact?</dt>

<dd>Please feel free to email any questions you have to 
<a href="mailto:team@hearfromyourmp.com">team@hearfromyourmp.com</a>.
</dd>

<dt id="remove">I'm really not interested at all. Can you remove my
name to stop people signing up?</dt>

<dd>We can advise people that you don't wish to use the site, and
point them elsewhere. However, as the list is per constituency, not
per MP, we will continue to accept subscribers. A future MP in your
constituency may want to use this service.</dd>

</dl>

</div>
<?
}

