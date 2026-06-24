<?php
/**
 * Interactive Form Command - Demo berbagai jenis input interaktif
 * 
 * Fitur:
 * - Text input dengan validasi
 * - Password input (tersembunyi)
 * - Pilihan (radio button style)
 * - Multiple choice (checkbox style)
 * - Konfirmasi (yes/no)
 * - Progress form
 */

use Hiya\Console\Command;

class FormCommand extends Command
{
    protected $name = 'form';
    protected $description = 'Interactive form demo with various input types';
    protected $aliases = ['survey', 'input-demo'];
    
    public function handle()
    {
        $this->showHeader();
        
        // Data collection
        $data = [];
        
        // 1. Text input dengan validasi
        $data['name'] = $this->inputText("Full Name", [
            'required' => true,
            'min' => 3,
            'max' => 50
        ]);
        
        // 2. Email input
        $data['email'] = $this->inputEmail("Email Address");
        
        // 3. Age input (numeric)
        $data['age'] = $this->inputNumber("Age", 18, 100);
        
        // 4. Phone number
        $data['phone'] = $this->inputPhone("Phone Number");
        
        // 5. Password (hidden)
        $data['password'] = $this->inputPassword("Password");
        
        // 6. Gender selection (radio button style)
        $data['gender'] = $this->inputRadio("Gender", [
            'male' => 'Male',
            'female' => 'Female',
            'other' => 'Other'
        ]);
        
        // 7. Multiple choice (checkbox style)
        $data['interests'] = $this->inputCheckbox("Interests", [
            'coding' => '💻 Coding',
            'design' => '🎨 Design',
            'music' => '🎵 Music',
            'sports' => '⚽ Sports',
            'reading' => '📚 Reading'
        ]);
        
        // 8. Select from list (dropdown style)
        $data['country'] = $this->inputSelect("Country", [
            'id' => 'Indonesia',
            'my' => 'Malaysia',
            'sg' => 'Singapore',
            'th' => 'Thailand',
            'vn' => 'Vietnam'
        ]);
        
        // 9. Rating (1-5)
        $data['rating'] = $this->inputRating("How would you rate our service?", 5);
        
        // 10. Feedback (textarea style)
        $data['feedback'] = $this->inputTextarea("Feedback / Comments");
        
        // 11. Confirmation
        $data['newsletter'] = $this->inputConfirm("Subscribe to newsletter?", true);
        
        // Show summary
        $this->showSummary($data);
        
        // Final confirmation
        if ($this->confirmSubmit($data)) {
            $this->saveData($data);
            $this->success("\n  ✓ Form submitted successfully!");
            $this->line("  Thank you for your response!\n");
        } else {
            $this->warning("\n  Form cancelled.\n");
        }
        
        return 0;
    }
    
    protected function showHeader()
    {
        $this->line("\n  <fg=cyan;options=bold>╔══════════════════════════════════════════════════════════════╗</>");
        $this->line("  <fg=cyan;options=bold>║</>              <fg=white>Interactive Form Demo</>                          <fg=cyan;options=bold>║</>");
        $this->line("  <fg=cyan;options=bold>╚══════════════════════════════════════════════════════════════╝</>");
        $this->line("");
        $this->info("Please fill out the following form:");
        $this->line("");
    }
    
    /**
     * Text input dengan validasi
     */
    protected function inputText($label, $rules = [])
    {
        $this->line("\n  <fg=yellow>📝 {$label}</>");
        
        $defaultRules = ['required' => true];
        $rules = array_merge($defaultRules, $rules);
        
        return $this->askValid("  Enter value", $rules);
    }
    
    /**
     * Email input
     */
    protected function inputEmail($label)
    {
        $this->line("\n  <fg=yellow>📧 {$label}</>");
        return $this->askValid("  Email address", ['required', 'email']);
    }
    
    /**
     * Phone number input
     */
    protected function inputPhone($label)
    {
        $this->line("\n  <fg=yellow>📞 {$label}</>");
        return $this->askValid("  Phone number", ['required', ['regex', '/^[0-9+\-\s()]+$/']]);
    }
    
    /**
     * Number input dengan range
     */
    protected function inputNumber($label, $min = null, $max = null)
    {
        $this->line("\n  <fg=yellow>🔢 {$label}</>");
        $rules = ['required', 'numeric'];
        if ($min && $max) {
            $rules[] = ['between', $min, $max];
        }
        return (int)$this->askValid("  Enter number", $rules);
    }
    
    /**
     * Password input (hidden)
     */
    protected function inputPassword($label)
    {
        $this->line("\n  <fg=yellow>🔒 {$label}</>");
        return $this->secret("  Enter password");
    }
    
    /**
     * Radio button style (single choice)
     */
    protected function inputRadio($label, $options)
    {
        $this->line("\n  <fg=yellow>🔘 {$label}</>");
        $this->line("  " . str_repeat('─', 40), 'gray');
        
        $keys = array_keys($options);
        foreach ($options as $key => $value) {
            $this->line("    <fg=cyan>[{$key}]</> {$value}", 'cyan');
        }
        $this->line("");
        
        $choice = $this->choice("  Select one", $options);
        return $choice;
    }
    
    /**
     * Checkbox style (multiple choice)
     */
    protected function inputCheckbox($label, $options)
    {
        $this->line("\n  <fg=yellow>☑️ {$label}</>");
        $this->line("  " . str_repeat('─', 40), 'gray');
        $this->line("  <fg=gray>Select multiple (comma separated, e.g: 1,2,3)</>", 'gray');
        $this->line("");
        
        $keys = array_keys($options);
        foreach ($options as $key => $value) {
            $this->line("    <fg=cyan>[{$key}]</> {$value}", 'cyan');
        }
        $this->line("");
        
        $selected = $this->ask("  Enter choices", implode(',', $keys));
        
        $selectedIds = array_map('trim', explode(',', $selected));
        $result = [];
        foreach ($selectedIds as $id) {
            if (isset($options[$id])) {
                $result[] = $options[$id];
            }
        }
        
        return $result;
    }
    
    /**
     * Select dropdown style
     */
    protected function inputSelect($label, $options)
    {
        $this->line("\n  <fg=yellow>📋 {$label}</>");
        $this->line("  " . str_repeat('─', 40), 'gray');
        
        foreach ($options as $key => $value) {
            $this->line("    <fg=cyan>[{$key}]</> {$value}", 'cyan');
        }
        $this->line("");
        
        $choice = $this->choice("  Select one", $options);
        return $choice;
    }
    
    /**
     * Rating input (1-5 stars)
     */
    protected function inputRating($label, $max = 5)
    {
        $this->line("\n  <fg=yellow>⭐ {$label}</>");
        $this->line("  " . str_repeat('─', 40), 'gray');
        
        $stars = [];
        for ($i = 1; $i <= $max; $i++) {
            $starDisplay = str_repeat('★', $i) . str_repeat('☆', $max - $i);
            $stars[$i] = "{$starDisplay} ({$i}/{$max})";
        }
        
        foreach ($stars as $key => $value) {
            $this->line("    <fg=cyan>[{$key}]</> {$value}", 'cyan');
        }
        $this->line("");
        
        $rating = (int)$this->askValid("  Select rating", ['numeric', ['between', 1, $max]]);
        return $rating;
    }
    
    /**
     * Textarea style (multi-line)
     */
    protected function inputTextarea($label)
    {
        $this->line("\n  <fg=yellow>💬 {$label}</>");
        $this->line("  <fg=gray>Enter your response (type 'END' on new line to finish)</>", 'gray');
        $this->line("");
        
        $lines = [];
        $this->line("  " . str_repeat('─', 40), 'gray');
        
        while (true) {
            $line = $this->ask("  ");
            if (strtoupper($line) === 'END') {
                break;
            }
            $lines[] = $line;
        }
        
        return implode("\n  ", $lines);
    }
    
    /**
     * Confirm input (yes/no)
     */
    protected function inputConfirm($label, $default = false)
    {
        $this->line("\n  <fg=yellow>❓ {$label}</>");
        return $this->confirm("  Confirm", $default);
    }
    
    /**
     * Show summary of all inputs
     */
    protected function showSummary($data)
    {
        $this->line("\n  <fg=cyan;options=bold>╔══════════════════════════════════════════════════════════════╗</>");
        $this->line("  <fg=cyan;options=bold>║</>                    <fg=white>Form Summary</>                          <fg=cyan;options=bold>║</>");
        $this->line("  <fg=cyan;options=bold>╠══════════════════════════════════════════════════════════════╣</>");
        
        $this->line(sprintf("  <fg=cyan;options=bold>║</> <fg=yellow>Name:</>        <fg=white>%-40s</> <fg=cyan;options=bold>║</>", $data['name']));
        $this->line(sprintf("  <fg=cyan;options=bold>║</> <fg=yellow>Email:</>       <fg=white>%-40s</> <fg=cyan;options=bold>║</>", $data['email']));
        $this->line(sprintf("  <fg=cyan;options=bold>║</> <fg=yellow>Age:</>         <fg=white>%-40s</> <fg=cyan;options=bold>║</>", $data['age']));
        $this->line(sprintf("  <fg=cyan;options=bold>║</> <fg=yellow>Phone:</>       <fg=white>%-40s</> <fg=cyan;options=bold>║</>", $data['phone']));
        $this->line(sprintf("  <fg=cyan;options=bold>║</> <fg=yellow>Gender:</>      <fg=white>%-40s</> <fg=cyan;options=bold>║</>", $data['gender']));
        
        $interests = is_array($data['interests']) ? implode(', ', $data['interests']) : $data['interests'];
        $this->line(sprintf("  <fg=cyan;options=bold>║</> <fg=yellow>Interests:</>   <fg=white>%-40s</> <fg=cyan;options=bold>║</>", $interests));
        
        $this->line(sprintf("  <fg=cyan;options=bold>║</> <fg=yellow>Country:</>     <fg=white>%-40s</> <fg=cyan;options=bold>║</>", $data['country']));
        
        $stars = str_repeat('★', $data['rating']) . str_repeat('☆', 5 - $data['rating']);
        $this->line(sprintf("  <fg=cyan;options=bold>║</> <fg=yellow>Rating:</>      <fg=white>%-40s</> <fg=cyan;options=bold>║</>", $stars));
        
        $this->line(sprintf("  <fg=cyan;options=bold>║</> <fg=yellow>Newsletter:</>  <fg=white>%-40s</> <fg=cyan;options=bold>║</>", $data['newsletter'] ? 'Yes' : 'No'));
        
        if (!empty($data['feedback'])) {
            $this->line("  <fg=cyan;options=bold>╠══════════════════════════════════════════════════════════════╣</>");
            $this->line("  <fg=cyan;options=bold>║</> <fg=yellow>Feedback:</>                                  <fg=cyan;options=bold>║</>");
            $feedbackLines = explode("\n", wordwrap($data['feedback'], 48));
            foreach ($feedbackLines as $line) {
                $this->line(sprintf("  <fg=cyan;options=bold>║</>   <fg=white>%-48s</> <fg=cyan;options=bold>║</>", $line));
            }
        }
        
        $this->line("  <fg=cyan;options=bold>╚══════════════════════════════════════════════════════════════╝</>");
    }
    
    /**
     * Confirm before submit
     */
    protected function confirmSubmit($data)
    {
        $this->line("\n  <fg=yellow>⚠️ Please review your answers above</>");
        return $this->confirm("  Submit this form?", true);
    }
    
    /**
     * Save data (simulasi)
     */
    protected function saveData($data)
    {
        // Simulate saving to database/file
        $filename = __DIR__ . '/../runtime/form_data_' . date('Ymd_His') . '.json';
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        $this->line("  <fg=gray>Data saved to: {$filename}</>");
    }
}