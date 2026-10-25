<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A slash command failed a business rule guard. The message is safe to show
 * to the user, and callers should surface it rather than logging it as a
 * change.
 */
class SlashCommandException extends RuntimeException {}
