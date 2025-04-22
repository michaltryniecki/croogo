//
//  jQuery Slug Plugin by Perry Trinier (perrytrinier@gmail.com)
//  MIT License: http://www.opensource.org/licenses/mit-license.php
//@TODO: This should rather load the slug using AJAX to ensure that an accurate slug is generated.
;(
  function ($) {
    function transliterate(str) {
      var rExps = [
        {
          re: /‰|a|?/g, ch: 'ae'
        }, {
          re: /ˆ|o/g, ch: 'oe'
        }, {
          re: /¸/g, ch: 'ue'
        }, {
          re: /ƒ/g, ch: 'Ae'
        }, {
          re: /‹/g, ch: 'Ue'
        }, {
          re: /÷/g, ch: 'Oe'
        }, {
          re: /A|¡|¬|A|ƒ|A|?|A|√|•|A/g, ch: 'A'
        }, {
          re: /a|·|‚|a|a|?|a|„|π|a|a/g, ch: 'a'
        }, {
          re: /«|∆|C|C|»/g, ch: 'C'
        }, {
          re: /Á|Ê|c|c|Ë/g, ch: 'c'
        }, {
          re: /?|œ|–/g, ch: 'D'
        }, {
          re: /?|Ô|/g, ch: 'd'
        }, {
          re: /E|…|E|À|E|E|E| |Ã/g, ch: 'E'
        }, {
          re: /e|È|e|Î|e|e|e|Í|Ï/g, ch: 'e'
        }, {
          re: /G|G|G|G/g, ch: 'G'
        }, {
          re: /g|g|g|g/g, ch: 'g'
        }, {
          re: /H|H/g, ch: 'H'
        }, {
          re: /h|h/g, ch: 'h'
        }, {
          re: /I|Õ|Œ|I|I|I|I|I|I|I/g, ch: 'I'
        }, {
          re: /i|Ì|Ó|i|i|i|i|i|i|i/g, ch: 'i'
        }, {
          re: /J/g, ch: 'J'
        }, {
          re: /j/g, ch: 'j'
        }, {
          re: /K/g, ch: 'K'
        }, {
          re: /k/g, ch: 'k'
        }, {
          re: /≈|L|º|?|£/g, ch: 'L'
        }, {
          re: /Â|l|æ|?|≥/g, ch: 'l'
        }, {
          re: /N|—|N|“/g, ch: 'N'
        }, {
          re: /n|Ò|n|Ú|?/g, ch: 'n'
        }, {
          re: /O|”|‘|O|O|O|O|’|O|O|?/g, ch: 'O'
        }, {
          re: /o|Û|Ù|o|o|o|o|ı|o|o|?|o/g, ch: 'o'
        }, {
          re: /¿|R|ÿ/g, ch: 'R'
        }, {
          re: /‡|r|¯/g, ch: 'r'
        }, {
          re: /å|S|™|ä/g, ch: 'S'
        }, {
          re: /ú|s|∫|ö|?/g, ch: 's'
        }, {
          re: /ﬁ|ç|T/g, ch: 'T'
        }, {
          re: /˛|ù|t/g, ch: 't'
        }, {
          re: /U|⁄|U|U|U|U|Ÿ|€|U|U|U|U|U|U|U/g, ch: 'U'
        }, {
          re: /u|˙|u|u|u|u|˘|˚|u|u|u|u|u|u|u/g, ch: 'u'
        }, {
          re: /›|Y|Y/g, ch: 'Y'
        }, {
          re: /˝|y|y/g, ch: 'y'
        }, {
          re: /W/g, ch: 'W'
        }, {
          re: /w/g, ch: 'w'
        }, {
          re: /è|Ø|é/g, ch: 'Z'
        }, {
          re: /ü|ø|û/g, ch: 'z'
        }, {
          re: /A|?/g, ch: 'AE'
        }, {
          re: /ﬂ/g, ch: 'ss'
        }, {
          re: /?/g, ch: 'IJ'
        }, {
          re: /?/g, ch: 'ij'
        }, {
          re: /O/g, ch: 'OE'
        }, {
          re: /f/g, ch: 'f'
        }, // Cyrillic Letters
        {
          re: /?/g, ch: 'A'
        }, {
          re: /?/g, ch: 'B'
        }, {
          re: /?/g, ch: 'V'
        }, {
          re: /?/g, ch: 'G'
        }, {
          re: /?/g, ch: 'D'
        }, {
          re: /?/g, ch: 'E'
        }, {
          re: /?/g, ch: 'YO'
        }, {
          re: /?/g, ch: 'ZH'
        }, {
          re: /?/g, ch: 'Z'
        }, {
          re: /?/g, ch: 'I'
        }, {
          re: /?/g, ch: 'Y'
        }, {
          re: /?/g, ch: 'K'
        }, {
          re: /?/g, ch: 'L'
        }, {
          re: /?/g, ch: 'M'
        }, {
          re: /?/g, ch: 'N'
        }, {
          re: /?/g, ch: 'O'
        }, {
          re: /?/g, ch: 'P'
        }, {
          re: /?/g, ch: 'R'
        }, {
          re: /?/g, ch: 'S'
        }, {
          re: /?/g, ch: 'T'
        }, {
          re: /?/g, ch: 'U'
        }, {
          re: /?/g, ch: 'F'
        }, {
          re: /?/g, ch: 'H'
        }, {
          re: /?/g, ch: 'TS'
        }, {
          re: /?/g, ch: 'CH'
        }, {
          re: /?/g, ch: 'SH'
        }, {
          re: /?/g, ch: 'SH'
        }, {
          re: /?/g, ch: ''
        }, {
          re: /?/g, ch: 'Y'
        }, {
          re: /?/g, ch: ''
        }, {
          re: /?/g, ch: 'E'
        }, {
          re: /?/g, ch: 'YU'
        }, {
          re: /?/g, ch: 'YA'
        }, {
          re: /?/g, ch: 'a'
        }, {
          re: /?/g, ch: 'b'
        }, {
          re: /?/g, ch: 'v'
        }, {
          re: /?/g, ch: 'g'
        }, {
          re: /?/g, ch: 'd'
        }, {
          re: /?/g, ch: 'e'
        }, {
          re: /?/g, ch: 'yo'
        }, {
          re: /?/g, ch: 'zh'
        }, {
          re: /?/g, ch: 'z'
        }, {
          re: /?/g, ch: 'i'
        }, {
          re: /?/g, ch: 'y'
        }, {
          re: /?/g, ch: 'k'
        }, {
          re: /?/g, ch: 'l'
        }, {
          re: /?/g, ch: 'm'
        }, {
          re: /?/g, ch: 'n'
        }, {
          re: /?/g, ch: 'o'
        }, {
          re: /?/g, ch: 'p'
        }, {
          re: /?/g, ch: 'r'
        }, {
          re: /?/g, ch: 's'
        }, {
          re: /?/g, ch: 't'
        }, {
          re: /?/g, ch: 'u'
        }, {
          re: /?/g, ch: 'f'
        }, {
          re: /?/g, ch: 'h'
        }, {
          re: /?/g, ch: 'ts'
        }, {
          re: /?/g, ch: 'ch'
        }, {
          re: /?/g, ch: 'sh'
        }, {
          re: /?/g, ch: 'sh'
        }, {
          re: /?/g, ch: ''
        }, {
          re: /?/g, ch: 'y'
        }, {
          re: /?/g, ch: ''
        }, {
          re: /?/g, ch: 'e'
        }, {
          re: /?/g, ch: 'yu'
        }, {
          re: /?/g, ch: 'ya'
        }
      ];
      for (var i = 0, len = rExps.length; i < len; i++) {
        str = str.replace(rExps[i].re, rExps[i].ch);
      }
      return str;
    }
    function makeSlug(input) {
      var slug = transliterate(jQuery.trim(input))
        .replace(/\s+/g, '-').replace(/[^a-zA-Z0-9\-]/g, '').toLowerCase()
        .replace(/\-{2,}/g, '-')
        .replace(/\-$/, '')
        .replace(/^\-/, '');
      return slug;
    }
    $.fn.slug = function (options) {
      var settings = {
        selector: '',
        slugClass: 'slug',
        hide: true,
        editable: true,
        editLabel: 'Edit',
        editClass: 'btn btn-secondary btn-sm'
      };

      if (options) {
        $.extend(settings, options);
      }

      return this.each(function () {
        var $target = $(this);
        var $slugInput = $($target.data('slug') ? $target.data('slug') : settings.selector);
        var $slugSpan = $('<span class="slug">&nbsp;</span>')
          .addClass($target.data('slugClass') ? $target.data('slugClass') : settings.slugClass);
        var $slugEdit = $('<a href="#" class="editable"></a>')
          .addClass($target.data('slugEditClass') ? $target.data('slugEditClass') : settings.editClass)
          .html($target.data('slugEditLabel') ? $target.data('slugEditLabel') : settings.editLabel);

        if ($target.data('slugEditLabel')) {
          $slugEdit.hide();
        }

        if (settings.hide || $target.data('slugHide')) {
          $slugInput
            .after($slugSpan)
            .hide();
        }
        if (settings.editable || $target.data('slugEditable')) {
          $slugSpan.after($slugEdit);
        }

        if ($slugInput.val()) {
          $slugSpan.text($slugInput.val());
          if (settings.editable) {
            $slugEdit.show();
          }
        }

        $target.on('keyup.slugger', function() {
          var slug = makeSlug($target.val());

          $slugInput.val(slug);
          $slugSpan.text(slug);

          if (settings.editable && slug) {
            $slugEdit.show();
          } else {
            if ($target.data('slugEditLabel')) {
              $slugEdit.hide();
            }
          }
        });

        $slugEdit.on('click.slugger', function(e) {
          e.preventDefault();
          $slugEdit.remove();
          $slugSpan.remove();
          $slugInput.show();
        });
      });
    };
  }(jQuery)
);

jQuery(function ($) {
  $(':input[data-slug]').slug();
});
