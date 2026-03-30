/**
 * Dynamic Features Admin script.
 */

(function() { 

    jQuery(function() {

        if (typeof $ === 'undefined') $ = jQuery;

        greyd.templates.init();
        
        console.log('Dynamic Template Scripts: loaded');
    } );

} )(jQuery);

//
// dynamic templates
greyd.templates = new function() {
    // events
    this.init = function() {
        var that = this;
        $(document).on('click', '#menu-posts-dynamic_template a[href*="post-new.php?post_type=dynamic_template"]', that.redirectToNew);
        $(document).on('click', '#wp-toolbar a[href*="post-new.php?post_type=dynamic_template"]', that.redirectToNew);
        $(document).on('click', '#wpbody-content .page-title-action[href*="post-new.php?post_type=dynamic_template"]', that.redirectToNew);

        // add class to indicate that wizard is loaded
        setTimeout(() => {
            $('#wpbody-content .page-title-action[href*="post-new.php?post_type=dynamic_template"]').addClass('wizard-loaded')
        }, 0);

        if ($('#greyd-wizard.template_wizard').length > 0) {
            // init AdminScreen
            $('.export_template').on('click', function() { that.export(this) });
            $('.import_template').on('click', function() { that.import(this) });
            $('#template_import').detach().insertBefore('.wp-header-end');
            $('#templates_tabs').detach().insertBefore('.subsubsub');
            $('#greyd-wizard #type').val('dynamic');
            this.selectType($('#greyd-wizard #type'));

            // init wizard
            $('#greyd-wizard #type').on('change', function() { that.selectType(this) });
            $('#greyd-wizard #header').on('change', function() { that.selectHeader(this) });
            $('#greyd-wizard #post_type').on('change', function() { that.selectPosttype(this) });
            $('#greyd-wizard .category').on('change', function() { that.selectCategory(this) });
            $('#greyd-wizard #shop').on('change', function() { that.selectShop(this) });
            $('#greyd-wizard #myaccount').on('change', function() { that.selectHook(this) });

            $('#greyd-wizard .content_option').on('click', function() { greyd.wizard.selectContent(this); });
            $('#greyd-wizard #name').on('input change', function() { greyd.wizard.checkExisting(dynamic_templates) });

            $('#greyd-wizard .double_reset').on('click', function() { that.createTemplate(this) });
            $('#greyd-wizard .create').on('click', function() { that.createTemplate(this) });

            // console.log(location.search);
            if (location.search == '?post_type=dynamic_template&wizard=add') greyd.wizard.initnew();
        }
        if ($('#template_type').length > 0) {
            $('#template_type').detach().insertBefore('#titlewrap');
            $('#template_type').css('display', 'block');
        }
        if ($('#template_log').length > 0) {
            // logging
            $('#template_log').detach().insertAfter('#titlewrap');
            $('#template_log').css('display', 'block');
            $('#template_log .toggle').each( function () {
                $(this).on('click', function () {
                    $(this).parent().toggleClass('show');
                });
            } );
        }
    }

    this.redirectToNew = function(e) {
        e.preventDefault();
        var url = window.location.href.split('wp-admin');
        if (url[1].indexOf("/edit.php?post_type=dynamic_template") > -1) greyd.wizard.initnew();
        else window.location.href = url[0]+"wp-admin/edit.php?post_type=dynamic_template&wizard=add";
    }

    this.createTemplate = function(el) {
        if ($(el).hasClass('disabled')) return;

        var result = {
            name: $('#greyd-wizard #name').val(),
            slug: $('#greyd-wizard #slug').val(),
            ttype: $('#greyd-wizard #ttype').val(),
            content: $('#greyd-wizard .content_option.active').attr('id'),
        }
        if ($(el).hasClass('double_reset')) {
            result['force'] = 'true';
            result['content'] = 'default';
        }
        if (result.content == 'copy') {
            if ($('#greyd-wizard #copy_'+result.ttype).length == 0) result.content = 'empty';
            else {
                result.copy = $('#greyd-wizard #copy_'+result.ttype).val();
                if (result.copy == "0") {
                    alert(theme_wordings.wizard.no_copy.replace('%s', 'Template'));
                    return;
                }
            }
        }
        if (result.name == "") {
            alert(theme_wordings.wizard.no_name.replace('%s', 'Template'));
            return;
        }

        // console.log("create template:");
        // console.log(result);
        greyd.wizard.createPost('create_template', result);
    }
    
    // wizard
    this.selectType = function(el) {
        var val = $(el).val();
        // console.log(val);
        $('#greyd-wizard #header').parent().css('display', 'none');
        $('#greyd-wizard #post_type').parent().css('display', 'none');
        $('#greyd-wizard .category').parent().css('display', 'none');
        $('#greyd-wizard #shop').parent().css('display', 'none');
        $('#greyd-wizard #myaccount').parent().css('display', 'none');
        $('#greyd-wizard #default').css('display', 'block');
        if (val == 'dynamic') {
            $('#greyd-wizard #name').val('');
            $('#greyd-wizard #slug').val('');
            $('#greyd-wizard #ttype').val('dynamic');
            $('#greyd-wizard #default').css('display', 'none');
        }
        else if (val == 'footer') {
            $('#greyd-wizard #name').val(theme_wordings.wizard.footer);
            $('#greyd-wizard #slug').val('footer');
            $('#greyd-wizard #ttype').val('navigation');
        }
        else if (val == 'offmenu') {
            $('#greyd-wizard #header').parent().css('display', 'block');
            this.selectHeader($('#greyd-wizard #header'));
        }
        else if (val == 'single' || val == 'archives' || val == 'search') {
            $('#greyd-wizard #post_type').parent().css('display', 'block');
            this.selectPosttype($('#greyd-wizard #post_type'));
        }
        else if (val == '404') {
            $('#greyd-wizard #name').val(theme_wordings.wizard.fourofour);
            $('#greyd-wizard #slug').val('404');
            $('#greyd-wizard #ttype').val('more');
        }
        else if (val == 'compatibility') {
            $('#greyd-wizard #name').val(theme_wordings.wizard.compatibility);
            $('#greyd-wizard #slug').val('compatibility');
            $('#greyd-wizard #ttype').val('more');
        }
        else if (val == 'woo_product') {
            $('#greyd-wizard #name').val(theme_wordings.wizard.woo_product);
            $('#greyd-wizard #slug').val('woo-product');
            $('#greyd-wizard #ttype').val('woo');
        }
        else if (val == 'woo_shop') {
            $('#greyd-wizard #shop').parent().css('display', 'block');
            this.selectShop($('#greyd-wizard #shop'));
        }
        else if (val == 'woo_myaccount') {
            $('#greyd-wizard #myaccount').parent().css('display', 'block');
            this.selectHook($('#greyd-wizard #myaccount'));
            $('#greyd-wizard #default').css('display', 'none');
        }
        greyd.wizard.setCopyContent();
        greyd.wizard.checkExisting(dynamic_templates);
    }
    this.selectHeader = function(el) {
        var val = $(el).val();
        // console.log(val);
        if (val == 'main') {
            $('#greyd-wizard #name').val(theme_wordings.wizard.main_menu);
            $('#greyd-wizard #slug').val('main-menu-offcanvas');
        }
        else {
            $('#greyd-wizard #name').val(theme_wordings.wizard.meta_menu);
            $('#greyd-wizard #slug').val('meta-menu-offcanvas');
        }
        $('#greyd-wizard #ttype').val('navigation');
        greyd.wizard.setCopyContent();
        greyd.wizard.checkExisting(dynamic_templates);
    }
    this.selectPosttype = function(el) {
        var val = $(el).val();
        // console.log(val);
        $('#greyd-wizard .category').parent().css('display', 'none');
        if (val != '0')
            $('#greyd-wizard #category_'+val).parent().css('display', 'block');
            this.selectCategory($('#greyd-wizard #category_'+val));
    }
    this.selectCategory = function(el) {
        var val = $(el).val();
        // console.log(val);
        var type = this.getType();
        $('#greyd-wizard #name').val(type.name);
        $('#greyd-wizard #slug').val(type.slug);
        $('#greyd-wizard #ttype').val(type.type);
        greyd.wizard.setCopyContent();
        greyd.wizard.checkExisting(dynamic_templates);
    }
    this.selectShop = function(el) {
        var val = $(el).val();
        // console.log(val);
        if (val == 'category') {
            $('#greyd-wizard #name').val(theme_wordings.wizard.woo_product_cat);
            $('#greyd-wizard #slug').val('woo-product-category');
        }
        else {
            $('#greyd-wizard #name').val(theme_wordings.wizard.woo_product_tag);
            $('#greyd-wizard #slug').val('woo-product-tag');
        }
        $('#greyd-wizard #ttype').val('woo');
        greyd.wizard.setCopyContent();
        greyd.wizard.checkExisting(dynamic_templates);
    }
    this.selectHook = function(el) {
        var val = $(el).val();
        var name = 'WooCommerce ' + $(el).find('option:selected').html();
        // console.log(val);
        $('#greyd-wizard #name').val(name);
        $('#greyd-wizard #slug').val(val);
        $('#greyd-wizard #ttype').val('woo');
        greyd.wizard.setCopyContent();
        greyd.wizard.checkExisting(dynamic_templates);
    }
    this.getType = function() {
        var name = "";
        var type = "";

        var slug = $('#greyd-wizard #type').val();
        if (slug == 'single') {
            name = theme_wordings.wizard.single;
            type = 'single';
        }
        else if (slug == 'archives') {
            name = theme_wordings.wizard.archives;
            type = 'archives';
        }
        else if (slug == 'search') {
            name = theme_wordings.wizard.search;
            type = 'search';
        }

        var posttype = $('#greyd-wizard #post_type').val();
        if (posttype != '0' && posttype != 'post') {
            var pt = $($('#greyd-wizard #post_type')).find('option:selected').text().split(' (');
            name += " "+theme_wordings.wizard.for_type.replace('%s', pt[0]);
            slug += '-' + posttype;
        }

        if (posttype != '0') {
            if ($('#greyd-wizard #category_'+posttype).length > 0) {
                var category = $('#greyd-wizard #category_'+posttype).val();
                if (category != '0') {
                    var cat = $($('#greyd-wizard #category_'+posttype)).find('option:selected').text().split(' (');
                    name += " "+theme_wordings.wizard.for_cat.replace('%s', cat[0]);
                    slug += '-' + category;
                }
            }
        }
        
        return {
            'name': name,
            'slug': slug, 
            'type': type, 
        };
    }

    // import/export
    this.export = function(el) {
        // console.log(el);
        try {
            var config = $(el).children(0).data('config');
            var data = $(el).children(0).data('data');
            // console.log(config);
            // console.log(data);
            $.post(
                greyd.ajax_url, {
                    'action': 'greyd_ajax',
                    '_ajax_nonce': greyd.nonce,
                    'mode': 'get_template',
                    'data': { id: config.template_id }
                }, 
                function(response) {
                    if (response.indexOf('error::') > -1) {
                        console.warn(response);
                        var tmp = response.split('error::');
                        // $('#greyd-wizard .error_msg').html(tmp[1]);
                        // greyd.wizard.setState('0-error');
                    }
                    else {
                        // console.info(response);
                        var mode = 'success';
                        if (response.indexOf('info::') > -1) mode = 'info';
                        var tmp = response.split(mode+'::');
                        data.content = encodeURIComponent(tmp[1]);

                        var pom = document.createElement('a');
                        pom.setAttribute('href', 'data:text/plain;charset=utf-8,' + JSON.stringify(data));
                        pom.setAttribute('download', config.file_name+'.data');
                
                        if (document.createEvent) {
                            var event = document.createEvent('MouseEvents');
                            event.initEvent('click', true, true);
                            pom.dispatchEvent(event);
                        }
                        else {
                            pom.click();
                        }
                    }
                }
            );
        }
        catch(err) {
            alert(config.domain+":\n\nUnable to export Template!");
        }
    }
    this.import = function(el) {
        // console.log(el);
        var config = JSON.parse(decodeURIComponent($(el).data('config')));
        var pom = document.createElement('input');
        pom.setAttribute('type', 'file');
        pom.setAttribute('accept', '.data');
        $(pom).on("change", function() { greyd.templates.readFile(this, config) });
        // pom.setAttribute('onchange', 'dynamic.readFile(this, "'+config.siteurl+'", "'+config.blog_id+'")' );
        if (document.createEvent) {
            var event = document.createEvent('MouseEvents');
            event.initEvent('click', true, true);
            pom.dispatchEvent(event);
        }
        else {
            pom.click();
        }
    }
    this.readFile = function(input, config) {
        // console.log("reading files ...");
        // console.log(input.files);
        // console.log(config);
        var new_siteurl = config.siteurl;
        var new_blog_id = config.blog_id;
        
        var tmp = new_siteurl.split('://');
        var domain = tmp[1];
        var file = input.files[0];
        if (file) {
            var reader = new FileReader();
            reader.readAsText(file, "UTF-8");
            reader.onload = function (evt) {
                // console.log(evt.target.result);
                try {
                    var data = JSON.parse(evt.target.result);
                    if (data['content'] == undefined || data['version'] == undefined) {
                        console.log(data);
                        alert(domain+":\n\nUnable to import Template!\n\nNot a Template data file!");
                        return;
                    }
                }
                catch(err) {
                    console.log("error reading file");
                    console.log(evt.target.result);
                    alert(domain+":\n\nUnable to import Template!\n\nNot a Template data file!");
                    return;
                }
                // get data
                var inputdata = greyd.templates.readData(data, new_siteurl, new_blog_id);
                if (inputdata) {
                    // console.log(data);
                    var result = {
                        name: data.title,
                        slug: data.slug,
                        ttype: data.type,
                        content: 'import',
                        import: encodeURIComponent(data.content)
                    }
                    // console.log("create template:");
                    // console.log(result);

                    $('#greyd-wizard .wizard_box').addClass('big');
                    greyd.wizard.open();
                    greyd.wizard.start();
                    greyd.wizard.setState(11);
                    var txt = $('.wizard_content .install_txt');
                    $.post(
                        greyd.ajax_url, {
                            'action': 'greyd_ajax',
                            '_ajax_nonce': greyd.nonce,
                            'mode': 'create_template',
                            'data': result
                        }, 
                        function(response) {
                            if (response.indexOf('error::') > -1) {
                                console.warn(response);
                                var tmp = response.split('error::');

                                $('#greyd-wizard .error_msg').html(tmp[1]);
                                greyd.wizard.setState('0-error');
                            }
                            else {
                                // console.info(response);
                                var mode = 'success';
                                if (response.indexOf('info::') > -1) mode = 'info';
                                var tmp = response.split(mode+'::');
                                var msg = tmp[1];
                                var url = location.href.split("wp-admin");
                                var post_id = msg.split('(id ');
                                post_id = post_id[1].split(')');
                                var edit = url[0]+"wp-admin/post.php?post="+post_id[0]+"&action=edit";

                                $('#greyd-wizard .finish_wizard').attr('href', edit);
                                $('#greyd-wizard .wizard_box').removeClass('big');
                                $(txt[0]).html($(txt[0]).html().replace('%s', result.name)); 
                                $(txt[0]).css('opacity', 1); 
                                greyd.wizard.setState(11);
                                setTimeout(function() { $(txt[1]).css('opacity', 1); }, 100);
                            }
                        }
                    );
                }
                else alert(domain+":\n\nUnable to import Template!\n\nNot a Template data file!");
            }
            reader.onerror = function (evt) {
                alert(domain+":\n\nUnable to import Template!\n\nError reading file");
            }
        }
    }
    this.readData = function(data, new_siteurl, new_blog_id) {
        if (!data['content']) return false;
        data['content'] = decodeURIComponent(data['content']);

        var old_blog_id = data['blog_id'];
        var old_siteurl = data['siteurl'].split('://');
        var old_http = old_siteurl[0];
        var old_domain = old_siteurl[1];

        var tmp = new_siteurl.split('://');
        var new_http = tmp[0];
        var new_domain = tmp[1];

        var old_url = old_http+":\/\/"+old_domain;
        var new_url = new_http+":\/\/"+new_domain;

        var old_uploads = "wp-content\/uploads";
        var new_uploads = "wp-content\/uploads";
        if (old_blog_id != "1") old_uploads += "\/sites\/"+old_blog_id;
        if (new_blog_id != "1") new_uploads += "\/sites\/"+new_blog_id;

        if (data['content'].indexOf(old_url) > -1)
            data['content'] = data['content'].split(old_url).join(new_url);
        if (data['content'].indexOf(old_uploads) > -1)
            data['content'] = data['content'].split(old_uploads).join(new_uploads);

        return data;
    }
    
    // edit title
    this.enterTitle = function(id) {
        // console.log("enter title for "+id);
        $('#title_'+id+' > a').css('display', 'inline');
    }
    this.leaveTitle = function(id) {
        // console.log("leave title for "+id);
        $('#title_'+id+' > a').css('display', 'none');
    }
    this.editTitle = function(id) {
        // console.log("edit title for "+id);
        $('#title_'+id).css('display', 'none');
        $('#title_edit_'+id).css('display', 'block');
    }
    this.resetTitle = function(id, old_title) {
        // console.log("reset title for "+id+" to "+old_title);
        $('#title_'+id).css('display', 'block');
        $('#title_edit_'+id).css('display', 'none');
        $('#title_edit_'+id+' > input').val(old_title);
    }
    this.setTitle = function(id, old_title) {
        // console.log("set title for "+id+" from "+old_title);
        if ($('#title_edit_'+id).hasClass('blocked')) {
            // console.log("blocked");
            return;
        }
        var new_title = $('#title_edit_'+id+' > input').val();
        // console.log("to "+new_title);
        if (new_title == "" || new_title == old_title) {
            this.resetTitle(id, old_title);
            return;
        }

		if (confirm("Are you sure? \n\n Changing the Template Name may lead to errors!")) {
            $('#title_'+id).css('display', 'block');
            $('#title_edit_'+id).css('display', 'none');

            // mod template name via ajax
            var result = {
                id: id,
                value: new_title,
            }
            // greyd_hub compatibility
            var ajax = { };
            if (typeof wizzard_details !== 'undefined')
                ajax = { ajax_url: wizzard_details.ajax_url, nonce: wizzard_details.nonce, action: "greyd_ajax" };
            else if (typeof greyd !== 'undefined')
                ajax = { ajax_url: greyd.ajax_url, nonce: greyd.nonce, action: "greyd_admin_ajax" };
            else return;

            $.post(
                ajax.ajax_url, {
                    'action': ajax.action,
                    '_ajax_nonce': ajax.nonce,
                    'mode': 'mod_template_name',
                    'data': result,
                }, 
                function(response) {
                    if (response.indexOf('error::') > -1) {
                        console.warn(response);
                        var tmp = response.split('error::');
                        var message = tmp[1];
                        $('#title_edit_'+id).removeClass('blocked');
                    }
                    else {
                        console.info( response );
                        var tmp = response.split('success::');
                        var tmp1 = tmp[1].split('new name value: ');
                        var title = tmp1[1];

                        $('#title_'+id+' > span').html(title);
                        $('#title_edit_'+id+' > input').val(title);
                        $('#title_edit_'+id).removeClass('blocked');
                    }
                }
            );
            $('#title_edit_'+id).addClass('blocked');
        }
    }
    
    // edit type
    this.enterType = function(id) {
        $('#type_'+id+' > a').css('display', 'inline');
    }
    this.leaveType = function(id) {
        $('#type_'+id+' > a').css('display', 'none');
    }
    this.editType = function(id) {
        $('#type_'+id).css('display', 'none');
        $('#type_edit_'+id).css('display', 'block');
    }
    this.resetType = function(id, old_type) {
        $('#type_'+id).css('display', 'block');
        $('#type_edit_'+id).css('display', 'none');
        $('#type_edit_'+id+' > input').val(old_type);
    }
    this.setType = function(id, old_type) {
        console.log("set type for "+id+" from "+old_type);
        if ($('#type_edit_'+id).hasClass('blocked')) {
            // console.log("blocked");
            return;
        }
        var new_type = $('#type_edit_'+id+' > select').val();
        console.log("to "+new_type);
        if (new_type == "" || new_type == old_type) {
            this.resetType(id, old_type);
            return;
        }

		if (confirm("Are you sure? \n\n Changing the Template Type may lead to errors!")) {
            $('#type_'+id).css('display', 'block');
            $('#type_edit_'+id).css('display', 'none');

            // mod template name via ajax
            var result = {
                id: id,
                value: new_type,
            }
            // greyd_hub compatibility
            var ajax = { };
            if (typeof wizzard_details !== 'undefined')
                ajax = { ajax_url: wizzard_details.ajax_url, nonce: wizzard_details.nonce, action: "greyd_ajax" };
            else if (typeof greyd !== 'undefined')
                ajax = { ajax_url: greyd.ajax_url, nonce: greyd.nonce, action: "greyd_admin_ajax" };
            else return;

            $.post(
                ajax.ajax_url, {
                    'action': ajax.action,
                    '_ajax_nonce': ajax.nonce,
                    'mode': 'mod_template_type',
                    'data': result,
                }, 
                function(response) {
                    if (response.indexOf('error::') > -1) {
                        console.warn(response);
                        var tmp = response.split('error::');
                        var message = tmp[1];
                        $('#type_edit_'+id).removeClass('blocked');
                    }
                    else {
                        console.info( response );
                        var tmp = response.split('success::');
                        var tmp1 = tmp[1].split('new type value: ');
                        var type = tmp1[1];

                        var types = {
                            'dynamic': { color: 'greyd', title: theme_wordings.template_types['dynamic'] },
                            'navigation': { color: 'blue', title: theme_wordings.template_types['navigation'] },
                            'single': { color: 'blue', title: theme_wordings.template_types['single'] },
                            'archives': { color: 'blue', title: theme_wordings.template_types['archives'] },
                            'search': { color: 'blue', title: theme_wordings.template_types['search'] },
                            'more': { color: 'blue', title: theme_wordings.template_types['more'] },
                            'woo': { color: 'purple', title: theme_wordings.template_types['woo'] }
                        };

                        $('#type_'+id+' > span').removeClass('greyd blue purple');
                        $('#type_'+id+' > span').addClass(types[type].color);
                        $('#type_'+id+' > span').html(types[type].title);
                        $('#type_edit_'+id+' > select').val(type);
                        $('#type_edit_'+id).removeClass('blocked');
                    }
                }
            );
            $('#type_edit_'+id).addClass('blocked');
        }
    }

}
