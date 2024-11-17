import { jsonrepair } from "jsonrepair";

export const convertResponseToBlocksArray = (response) => {
    const blocksArray = Array.isArray(response) ? response : [response];
    return blocksArray;
};

export const convertResponseStringToArray = (response) => {
    try {
        console.log("convert");
        const response_string = jsonrepair(response.trim());
        let parsedResponse;
        console.log("jsonrepair");

        if (response_string.includes("\n")) {
            const response_split = response_string.split("\n");
            if (response_split.length > 1) {
                parsedResponse = response_split.map((item) => JSON.parse(item));
            } else {
                parsedResponse = JSON.parse(response_string);
            }
        } else {
            parsedResponse = JSON.parse(response_string);
        }

        return parsedResponse;
    } catch (error) {
        // Let the error propagate up to be handled by the component
        throw new Error(`Failed to parse JSON: ${error.message}`);
    }
};

export const getAvailableBlockNames = () => {
    const blockTypes = wp.blocks.getBlockTypes();
    const availableBlockNames = blockTypes.map((type) => type.name);
    return availableBlockNames;
};
