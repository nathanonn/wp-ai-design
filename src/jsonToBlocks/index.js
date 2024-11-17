import { registerBlockType } from "@wordpress/blocks";
import Edit from "./edit";
import Save from "./save";

registerBlockType("ai-design-block/json-to-blocks", {
    title: "JSON To Blocks",
    icon: "editor-code",
    category: "design",
    attributes: {
        content: {
            type: "string",
            default: "",
        },
    },
    edit: Edit,
    save: Save,
});
