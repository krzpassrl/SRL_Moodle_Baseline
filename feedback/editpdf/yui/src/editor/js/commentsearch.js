var COMMENTSEARCHNAME = "commentsearch",
    COMMENTSEARCH;

/**
 * Provides an in browser PDF editor.
 *
 * @module moodle-seplfeedback_editpdf-editor
 */

/**
 * This is a searchable dialogue of comments.
 *
 * @namespace M.seplfeedback_editpdf
 * @class commentsearch
 * @constructor
 * @extends M.core.dialogue
 */
COMMENTSEARCH = function(config) {
    config.draggable = false;
    config.centered = true;
    config.width = '400px';
    config.visible = false;
    config.headerContent = M.util.get_string('searchcomments', 'seplfeedback_editpdf');
    config.footerContent = '';
    COMMENTSEARCH.superclass.constructor.apply(this, [config]);
};

Y.extend(COMMENTSEARCH, M.core.dialogue, {
    /**
     * Initialise the menu.
     *
     * @method initializer
     * @return void
     */
    initializer : function(config) {
        var editor,
            container,
            placeholder,
            commentfilter,
            commentlist,
            bb;

        bb = this.get('boundingBox');
        bb.addClass('seplfeedback_editpdf_commentsearch');

        editor = this.get('editor');
        container = Y.Node.create('<div/>');

        placeholder = M.util.get_string('filter', 'seplfeedback_editpdf');
        commentfilter = Y.Node.create('<input type="text" size="20" placeholder="' + placeholder + '"/>');
        container.append(commentfilter);
        commentlist = Y.Node.create('<ul role="menu" class="seplfeedback_editpdf_menu"/>');
        container.append(commentlist);

        commentfilter.on('keyup', this.filter_search_comments, null, this);
        commentlist.delegate('click', this.focus_on_comment, 'a', this);
        commentlist.delegate('key', this.focus_on_comment, 'enter,space', 'a', this);

        // Set the body content.
        this.set('bodyContent', container);

        COMMENTSEARCH.superclass.initializer.call(this, config);
    },

    /**
     * Event handler to filter the list of comments.
     *
     * @protected
     * @method filter_search_comments
     */
    filter_search_comments : function() {
        var filternode,
            commentslist,
            filtertext;

        filternode = Y.one(SELECTOR.SEARCHFILTER);
        commentslist = Y.one(SELECTOR.SEARCHCOMMENTSLIST);

        filtertext = filternode.get('value');

        commentslist.all('li').each(function (node) {
            if (node.get('text').indexOf(filtertext) !== -1) {
                node.show();
            } else {
                node.hide();
            }
        });
    },

    /**
     * Event handler to focus on a selected comment.
     *
     * @param Event e
     * @protected
     * @method focus_on_comment
     */
    focus_on_comment : function(e) {
        e.preventDefault();
        var target = e.target.ancestor('li'),
            comment = target.getData('comment'),
            editor = this.get('editor');

        this.hide();

        if (comment.pageno === editor.currentpage) {
            comment.drawable.nodes[0].one('textarea').focus();
        } else {
            // Comment is on a different page.
            editor.currentpage = comment.pageno;
            editor.change_page();
            comment.drawable.nodes[0].one('textarea').focus();
        }
    },

    /**
     * Show the menu.
     *
     * @method show
     * @return void
     */
    show : function() {
        var commentlist = this.get('boundingBox').one('ul'),
            editor = this.get('editor');

        commentlist.all('li').remove(true);

        // Rebuild the latest list of comments.
        Y.each(editor.pages, function(page) {
            Y.each(page.comments, function(comment) {
                var commentnode = Y.Node.create('<li><a href="#" tabindex="-1"><pre>' + comment.rawtext + '</pre></a></li>');
                commentlist.append(commentnode);
                commentnode.setData('comment', comment);
            }, this);
        }, this);

        this.centerDialogue();
        COMMENTSEARCH.superclass.show.call(this);
    }
}, {
    NAME : COMMENTSEARCHNAME,
    ATTRS : {
        /**
         * The editor this search window is attached to.
         *
         * @attribute editor
         * @type M.seplfeedback_editpdf.editor
         * @default null
         */
        editor : {
            value : null
        }

    }
});

Y.Base.modifyAttrs(COMMENTSEARCH, {
    /**
     * Whether the widget should be modal or not.
     *
     * Moodle override: We override this for commentsearch to force it always true.
     *
     * @attribute Modal
     * @type Boolean
     * @default true
     */
    modal: {
        getter: function() {
            return true;
        }
    }
});

M.seplfeedback_editpdf = M.seplfeedback_editpdf || {};
M.seplfeedback_editpdf.commentsearch = COMMENTSEARCH;
