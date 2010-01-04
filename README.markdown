**The Problem**

Creating a personal website formerly requires either an immensely complex folder tree of HTML files or an overblown CMS that does way beyond what the average user needs.

**The Solution**

Extend on XHTML such that a single file can describe not only a single page, but an entire website. SWS merely requires PHP5, and can optionally use mod_rewrite for pretty URLs. You don't even need access to a database.

**A Hello World**

template.sws

    <site title="John Doe" template="default.tmpl" css="main.css">
        <page link="Home">
            <header>My Home Page</header>
            <content>
                <p>Hello World!</p>
            </content>
        </page>
        <page link="Contact me">
            <header>To contact me</header>
            <content>
                <p>choose a medium</p>
            </content>
            <subpages>
                <page link="Email">
                    <header>Email</header>
                    <content>
                        <p>superhappyfuntime@me.com</p>
                    </content>
                </page>
                <page link="IM">
                    <header>IM</header>
                    <content>
                        <p>IM superhappyfuntime</p>
                    </content>
                </page>
            </subpages>
        </page>
    </site>

default.tmpl

    <html>
    <head>
        <title />
    </head>
    <body>
        <div id="alllinks" />
        <h3 id="header" />
        <div id="content" />
    </body>
    </html>

main.css - Your run of the mill CSS stylesheet (empty for this example)

**Features**

- No maintaining a file tree. Want a new page? Add a page element to the site tag.
- No compiling required
- Automatic clean URL generation (assuming you have mod_rewrite enabled)
- Automatically generated links (and nested links)
- Templatizing of standard HTML - no new syntax required
- Virtually infinite nested pages

**History**

January 3rd, 2010 - Static Compilation added, added a rewrite rule to force append slashes
December 24, 2009 - Initial Release

