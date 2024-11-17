import React from "react";
import { __ } from "@wordpress/i18n";
import { FormControl, InputLabel, Select, MenuItem, Box } from "@mui/material";
import ProviderSettings from "./ProviderSettings";

function ModelsSettings({
    settings,
    customApiUrl,
    setCustomApiUrl,
    handleInputChange,
    handleToggleProvider,
    refreshModelsTrigger,
    onOpenApiKeyDialog,
    onAddModel,
    onRemoveModel,
    defaultModel,
    setDefaultModel,
    defaultImageAnalysisModel,
    setDefaultImageAnalysisModel,
    availableModels,
    handleToggleCustomPrompt,
}) {
    return (
        <Box sx={{ minHeight: "100vh" }}>
            <FormControl fullWidth sx={{ mb: 4 }}>
                <InputLabel id="default-model-label">
                    {__("Default Model", "ai-design-block")}
                </InputLabel>
                <Select
                    labelId="default-model-label"
                    value={defaultModel}
                    onChange={(e) => setDefaultModel(e.target.value)}
                    label={__("Default Model", "ai-design-block")}
                >
                    {availableModels.map((model) => (
                        <MenuItem key={model.value} value={model.value}>
                            {model.label}
                        </MenuItem>
                    ))}
                </Select>
            </FormControl>
            <FormControl fullWidth sx={{ mb: 4 }}>
                <InputLabel id="default-image-analysis-model-label">
                    {__("Image Analysis Model", "ai-design-block")}
                </InputLabel>
                <Select
                    labelId="default-image-analysis-model-label"
                    value={defaultImageAnalysisModel}
                    onChange={(e) =>
                        setDefaultImageAnalysisModel(e.target.value)
                    }
                    label={__(
                        "Default Image Analysis Model",
                        "ai-design-block"
                    )}
                >
                    {availableModels.map((model) => (
                        <MenuItem key={model.value} value={model.value}>
                            {model.label}
                        </MenuItem>
                    ))}
                </Select>
            </FormControl>
            {Object.entries(settings).map(([provider, providerSettings]) => (
                <ProviderSettings
                    key={provider}
                    provider={provider}
                    providerSettings={providerSettings}
                    customApiUrl={customApiUrl}
                    setCustomApiUrl={setCustomApiUrl}
                    handleInputChange={handleInputChange}
                    handleToggleProvider={handleToggleProvider}
                    handleToggleCustomPrompt={handleToggleCustomPrompt}
                    refreshModelsTrigger={refreshModelsTrigger}
                    onOpenApiKeyDialog={() => onOpenApiKeyDialog(provider)}
                    onAddModel={onAddModel}
                    onRemoveModel={onRemoveModel}
                />
            ))}
        </Box>
    );
}

export default ModelsSettings;
