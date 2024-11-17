import { __ } from "@wordpress/i18n";
import EditComponent from "./EditComponent";
import SaveComponent from "./SaveComponent";

const blockConfig = {
    title: __("AI Design Block", "ai-design-block"),
    icon: "admin-customizer",
    category: "design",
    attributes: {
        content: {
            type: "string",
            default: "",
        },
        selectedProvider: {
            type: "string",
            default: "",
        },
        selectedModel: {
            type: "string",
            default: "",
        },
    },
    edit: EditComponent,
    save: SaveComponent,
};

export default blockConfig;