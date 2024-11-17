import React, { useState, useEffect } from "react";
import {
    Box,
    FormControl,
    InputLabel,
    Select,
    MenuItem,
    Button,
    Chip,
} from "@mui/material";
import { __ } from "@wordpress/i18n";
import { getProviderModels } from "../services/api";

function ModelList({
    provider,
    models,
    onAddModel,
    onRemoveModel,
    refreshTrigger,
}) {
    const [availableModels, setAvailableModels] = useState([]);
    const [selectedModel, setSelectedModel] = useState("");
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        fetchModels();
    }, [provider, refreshTrigger]);

    const fetchModels = async () => {
        setIsLoading(true);
        try {
            const fetchedModels = await getProviderModels(provider);
            setAvailableModels(fetchedModels);
        } catch (error) {
            console.error("Error fetching models:", error);
        }
        setIsLoading(false);
    };

    const handleAddModel = () => {
        if (selectedModel) {
            onAddModel(selectedModel);
            setSelectedModel("");
        }
    };

    return (
        <Box>
            <Box sx={{ display: "flex", alignItems: "center", mb: 2 }}>
                <FormControl fullWidth sx={{ mr: 2 }}>
                    <InputLabel id={`${provider}-model-select-label`}>
                        {__("Select AI Model", "ai-design-block")}
                    </InputLabel>
                    <Select
                        labelId={`${provider}-model-select-label`}
                        value={selectedModel}
                        onChange={(e) => setSelectedModel(e.target.value)}
                        label={__("Select AI Model", "ai-design-block")}
                        disabled={isLoading}
                    >
                        {availableModels.map((model) => (
                            <MenuItem key={model} value={model}>
                                {model}
                            </MenuItem>
                        ))}
                    </Select>
                </FormControl>
                <Button
                    onClick={handleAddModel}
                    variant="contained"
                    disabled={!selectedModel || isLoading}
                >
                    {__("Add", "ai-design-block")}
                </Button>
            </Box>
            <Box sx={{ display: "flex", flexWrap: "wrap", gap: 1 }}>
                {models.map((model) => (
                    <Chip
                        key={model}
                        label={model}
                        onDelete={() => onRemoveModel(model)}
                        color="primary"
                        variant="outlined"
                    />
                ))}
            </Box>
        </Box>
    );
}

export default ModelList;
