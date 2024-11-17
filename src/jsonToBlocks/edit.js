import { useBlockProps } from "@wordpress/block-editor";
import { TextareaControl, Button } from "@wordpress/components";
import { useState } from "@wordpress/element";
import { useDispatch } from "@wordpress/data";
import { createBlock } from "@wordpress/blocks";
import { __ } from "@wordpress/i18n";
import {
    convertResponseToBlocksArray,
    getAvailableBlockNames,
} from "../block/utils";

function Edit({ attributes, setAttributes, clientId }) {
    const [error, setError] = useState(null);
    const { replaceBlock } = useDispatch("core/block-editor");
    const blockProps = useBlockProps();

    const convertToBlocks = () => {
        try {
            const parsedContent = JSON.parse(attributes.content.trim());
            const blocksArray = convertResponseToBlocksArray(parsedContent);
            const newBlocks = processApiResponse(blocksArray);
            if (newBlocks.length > 0) {
                replaceBlock(clientId, newBlocks);
            }
        } catch (err) {
            console.error(err);
            setError("Invalid JSON format. Please check your input.");
        }
    };

    const processApiResponse = (blocks) => {
        const availableBlockNames = getAvailableBlockNames();

        const createBlocks = (blocks) => {
            return blocks
                .filter((block) => availableBlockNames.includes(block.name))
                .map((block) => {
                    const innerBlocks = block.innerBlocks
                        ? createBlocks(block.innerBlocks)
                        : [];
                    return createBlock(
                        block.name,
                        block.attributes,
                        innerBlocks
                    );
                });
        };
        return createBlocks(blocks);
    };

    return (
        <div {...blockProps}>
            <TextareaControl
                label={__("JSON To Blocks", "ai-design-block")}
                value={attributes.content}
                onChange={(content) => setAttributes({ content })}
                rows={10}
            />
            <Button variant="primary" onClick={convertToBlocks}>
                {__("Convert to Blocks", "ai-design-block")}
            </Button>
            {error && <p className="error-message">{error}</p>}
        </div>
    );
}

export default Edit;
