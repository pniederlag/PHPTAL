<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004-2005 Laurent Bedubourg
//  
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//  
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//  
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//  
//  Authors: Laurent Bedubourg <lbedubourg@motion-twin.com>
//  

// METAL Specification 1.0
//
//      argument ::= expression
//
// Example:
// 
//      <hr />
//      <p metal:use-macro="here/master_page/macros/copyright">
//      <hr />
//
// PHPTAL: (here not supported)
//
//      <?php echo phptal_macro( $tpl, 'master_page.html/macros/copyright'); ? >
//

/**
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_METAL_UseMacro extends PHPTAL_Php_Attribute
{
    public function start()
    {
        $this->pushSlots();
        
        foreach ($this->tag->children as $child){
            $this->generateFillSlots($child);
        }

        // local macro (no filename specified) and non dynamic macro name
        if (preg_match('/^[a-z0-9_]+$/i', $this->expression)){
            $code = sprintf(
                '%s%s($tpl, $ctx)', 
                $this->tag->generator->getFunctionPrefix(),
                $this->expression
            );
            $this->tag->generator->pushCode($code);
        }
        // external macro or ${macroname}, use PHPTAL at runtime to resolve it
        else {
            $code = $this->tag->generator->evaluateTalesString($this->expression);
            $code = sprintf('<?php $tpl->executeMacro(%s); ?>', $code);
            $this->tag->generator->pushHtml($code);
        }

        $this->popSlots();
    }
    
    public function end()
    {
    }

    private function pushSlots()
    {
        // reset template slots on each macro call ?
        // 
        // NOTE: defining a macro and using another macro on the same tag 
        // means inheriting from the used macro, thus slots are shared, it 
        // is a little tricky to understand but very natural to use.
        //
        // For example, we may have a main design.html containing our main 
        // website presentation with some slots (menu, content, etc...) then
        // we may define a member.html macro which use the design.html macro
        // for the general layout, fill the menu slot and let caller templates
        // fill the parent content slot without interfering. 
        if (!$this->tag->hasAttribute('metal:define-macro')){
            $this->tag->generator->pushCode('$ctx->pushSlots()');
        }
    }

    private function popSlots()
    {
        // restore slots if not inherited macro
        if (!$this->tag->hasAttribute('metal:define-macro')){
            $this->tag->generator->pushCode('$ctx->popSlots()');
        }
    }
    
    private function generateFillSlots($tag)
    {
        $allowedAtts = array(
            'metal:fill-slot', 'metal:define-macro', 'tal:define'
        );
                              
        if (false == ($tag instanceOf PHPTAL_Php_NodeTree)) 
            return;

        // if the tag contains one of the allowed attribute, we generate it
        foreach ($allowedAtts as $attribute){
            if ($tag->hasAttribute($attribute)){
                $tag->generate();
                return;
            }
        }
        
        // recurse
        foreach ($tag->children as $child){
            $this->generateFillSlots($child);
        }
    }
}

?>
