<?php

namespace App\Http\Controllers;

use Chatify\Http\Controllers\MessagesController as BaseMessagesController;

/**
 * MessagesController - Extends Chatify's base controller
 * 
 * This controller extends the default Chatify MessagesController
 * to maintain compatibility while allowing custom route overrides
 * via OptimizedMessagesController.
 */
class MessagesController extends BaseMessagesController
{
    // Inherits all methods from Chatify\Http\Controllers\MessagesController
    // Specific optimized methods are in OptimizedMessagesController
}
