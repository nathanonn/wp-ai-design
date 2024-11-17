import React from "react";
import {
    Typography,
    TextField,
    FormControlLabel,
    Grid,
    Box,
    Switch,
} from "@mui/material";
import { __ } from "@wordpress/i18n";
import ApiKeyField from "./ApiKeyField";
import ModelList from "./ModelList";

function ProviderSettings({
    provider,
    providerSettings,
    customApiUrl,
    setCustomApiUrl,
    handleInputChange,
    handleToggleProvider,
    refreshModelsTrigger,
    onOpenApiKeyDialog,
    onAddModel,
    onRemoveModel,
    handleToggleCustomPrompt,
}) {
    return (
        <Grid item xs={12}>
            <Box
                sx={{
                    border: "1px solid #e0e0e0",
                    borderRadius: "8px",
                    overflow: "hidden",
                    mb: 3,
                }}
            >
                <Box
                    sx={{
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "space-between",
                        p: 2,
                        borderBottom: "1px solid #e0e0e0",
                    }}
                >
                    <Typography variant="h6">
                        {__(
                            `${
                                provider.charAt(0).toUpperCase() +
                                provider.slice(1)
                            } Settings`,
                            "ai-design-block"
                        )}
                    </Typography>
                    <Box>
                        <FormControlLabel
                            control={
                                <Switch
                                    checked={providerSettings.enabled}
                                    onChange={() =>
                                        handleToggleProvider(provider)
                                    }
                                />
                            }
                            label={__("Enable", "ai-design-block")}
                        />
                    </Box>
                </Box>
                {providerSettings.enabled && (
                    <Box sx={{ p: 2 }}>
                        {provider === "custom" && (
                            <TextField
                                fullWidth
                                label={__("Custom API URL", "ai-design-block")}
                                value={customApiUrl}
                                onChange={(e) =>
                                    setCustomApiUrl(e.target.value)
                                }
                                margin="normal"
                                sx={{
                                    "& .MuiInputLabel-root": {
                                        transform:
                                            "translate(14px, 16px) scale(1)",
                                    },
                                    "& .MuiInputLabel-shrink": {
                                        transform:
                                            "translate(14px, -6px) scale(0.75)",
                                    },
                                    "& .MuiInputBase-root": {
                                        fontSize: "1.1rem",
                                        padding: "8px 14px",
                                    },
                                    '& input[type="text"]': {
                                        border: "none",
                                        "&:focus": {
                                            border: "none",
                                            outline: "none",
                                            boxShadow: "none",
                                        },
                                    },
                                }}
                            />
                        )}
                        <ApiKeyField
                            provider={provider}
                            apiKey={providerSettings.apiKey}
                            onChange={(value) =>
                                handleInputChange(provider, "apiKey", value)
                            }
                            label={
                                provider === "gemini"
                                    ? __(
                                          "API Key (Google AI Studio)",
                                          "ai-design-block"
                                      )
                                    : __("API Key", "ai-design-block")
                            }
                            onOpenDialog={() => onOpenApiKeyDialog(provider)}
                        />
                        <ModelList
                            provider={provider}
                            models={providerSettings.aiModels || []}
                            onAddModel={(model) => onAddModel(provider, model)}
                            onRemoveModel={(model) =>
                                onRemoveModel(provider, model)
                            }
                            refreshTrigger={refreshModelsTrigger}
                        />
                        <Box sx={{ mt: 2 }}>
                            <FormControlLabel
                                control={
                                    <Switch
                                        checked={
                                            providerSettings.customPromptEnabled
                                        }
                                        onChange={() =>
                                            handleToggleCustomPrompt(provider)
                                        }
                                    />
                                }
                                label={__(
                                    "Enable Custom System Prompt",
                                    "ai-design-block"
                                )}
                            />
                            {providerSettings.customPromptEnabled && (
                                <TextField
                                    fullWidth
                                    label={__(
                                        "Custom System Prompt",
                                        "ai-design-block"
                                    )}
                                    multiline
                                    rows={6}
                                    value={providerSettings.customPrompt}
                                    onChange={(e) =>
                                        handleInputChange(
                                            provider,
                                            "customPrompt",
                                            e.target.value
                                        )
                                    }
                                    margin="normal"
                                    sx={{
                                        "& .MuiInputLabel-root": {
                                            transform:
                                                "translate(14px, 16px) scale(1)",
                                        },
                                        "& .MuiInputLabel-shrink": {
                                            transform:
                                                "translate(14px, -6px) scale(0.75)",
                                        },
                                        "& .MuiInputBase-root": {
                                            fontSize: "1.1rem",
                                            padding: "8px 14px",
                                        },
                                        "& textarea": {
                                            border: "none",
                                            "&:focus": {
                                                border: "none",
                                                outline: "none",
                                                boxShadow: "none",
                                            },
                                        },
                                    }}
                                />
                            )}
                        </Box>
                    </Box>
                )}
            </Box>
        </Grid>
    );
}

export default ProviderSettings;
