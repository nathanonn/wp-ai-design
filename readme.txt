=== WP AI Design ===

Contributors:      Nathan Onn
Tags:              ai, design, block, pattern, llm, openai, anthropic, gemini, gpt-4o, claude-3.5-sonnet, gemini-1.5-pro
Tested up to:      6.1
Stable tag:        0.1.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

A WordPress block plugin that leverages AI to generate designs from text or images using various Language Learning Models (LLMs).

== Description ==

WP AI Design integrates multiple AI providers (OpenAI, Anthropic, Google Gemini) to help you generate WordPress block patterns and layouts directly in the block editor. Simply describe what you want, or upload a screenshot and the AI will generate the appropriate blocks and patterns.

== Features ==

- 🤖 Multiple AI Provider Support
  - OpenAI
  - Anthropic Claude
  - Google Gemini
- 🎨 Block Pattern Generation
- 📝 Custom System Prompt
- 📊 Logging System
- ⚙️ Configurable Settings

== Quick Start Guide ==

Visit the [Quick Start Guide](https://smashingadvantage.notion.site/WP-AI-Design-Quick-Start-Guide-144671927e128058815beccdb930e57a) for step-by-step instructions on setting up and using the plugin.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wp-ai-design`, or install through WordPress plugins screen
2. Activate the plugin through 'Plugins' screen
3. Configure API keys in Settings → WP AI Design

== Configuration ==

Access plugin settings at `Settings → AI Design Block` to:

- Configure API keys
- Add AI models
- Set default models
- Add custom system prompts
- View logs

== Usage ==

1. Add the "AI Design Block" to your post/page
2. Select your preferred AI provider
3. Describe the design you want or upload an image
4. The AI will generate the corresponding WordPress blocks

== Development ==

### Prerequisites

- Node.js
- npm/yarn
- WordPress development environment

### Setup

#### Install dependencies

```bash
npm install
```

#### Build assets

```bash
npm run build
```

#### Development mode

```bash
npm run start
```

### File Structure

```
wp-ai-design/
├── build/ # Compiled assets
├── src/ # React/JS source files
│ ├── block/ # Main block components
│ ├── blockTest/ # Testing utilities
│ └── settings/ # Settings page components
├── includes/ # PHP classes
│ ├── api/ # AI provider integrations
│ └── prompt/ # AI system prompts
└── wp-ai-design.php # Plugin main file
```

== Contributing ==

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

== License ==

GPL-2.0-or-later. See [LICENSE](LICENSE) for more information.

== Credits ==

Built by [Nathan Onn](https://www.nathanonn.com)
