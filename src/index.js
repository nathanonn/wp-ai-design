import { registerBlockType } from "@wordpress/blocks";
import { registerPlugin } from "@wordpress/plugins";
import { PluginMoreMenuItem } from "@wordpress/editor";
import { createRoot } from "@wordpress/element";
import SettingsPage from "./settings";

import "./block/block.scss";

// register block
import blockConfig from "./block";
registerBlockType("ai-design-block/main", blockConfig);

import("./jsonToBlocks").then((module) => {
    const jsonToBlocksConfig = module.default;
    registerBlockType("ai-design-block/json-to-blocks", jsonToBlocksConfig);
});

// Register settings page
function initializeSettingsPage() {
    const settingsContainer = document.getElementById(
        "ai-design-block-settings"
    );
    if (settingsContainer) {
        createRoot(settingsContainer).render(<SettingsPage />);
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeSettingsPage);
} else {
    initializeSettingsPage();
}
