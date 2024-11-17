import { useState, useEffect, useCallback, useMemo } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import {
    Container,
    Typography,
    Button,
    Paper,
    Snackbar,
    Alert,
    Tabs,
    Tab,
    Box,
    AppBar,
    Toolbar,
} from "@mui/material";
import { getSettings, updateSettings } from "./services/api";
import ModelsSettings from "./components/ModelsSettings";
import LoggerSettings from "./components/LoggerSettings";
import ApiKeyDialog from "./components/ApiKeyDialog";

function TabPanel(props) {
    const { children, value, index, ...other } = props;

    return (
        <div
            role="tabpanel"
            hidden={value !== index}
            id={`vertical-tabpanel-${index}`}
            aria-labelledby={`vertical-tab-${index}`}
            {...other}
        >
            {value === index && <Box sx={{ p: 3 }}>{children}</Box>}
        </div>
    );
}

function SettingsPage() {
    const [settings, setSettings] = useState({});
    const [customApiUrl, setCustomApiUrl] = useState("");
    const [isLoading, setIsLoading] = useState(true);
    const [snackbar, setSnackbar] = useState({
        open: false,
        message: "",
        severity: "success",
    });
    const [openDialog, setOpenDialog] = useState(false);
    const [currentProvider, setCurrentProvider] = useState(null);
    const [refreshModelsTrigger, setRefreshModelsTrigger] = useState(0);
    const [defaultModel, setDefaultModel] = useState("");
    const [defaultImageAnalysisModel, setDefaultImageAnalysisModel] =
        useState("");
    const [tabValue, setTabValue] = useState(0);

    const availableModels = useMemo(() => {
        return Object.entries(settings).flatMap(
            ([provider, providerSettings]) => {
                if (
                    providerSettings.enabled &&
                    providerSettings.aiModels.length > 0
                ) {
                    return providerSettings.aiModels.map((model) => ({
                        value: `${provider}_|_${model}`,
                        label: `${provider} - ${model}`,
                    }));
                }
                return [];
            }
        );
    }, [settings]);

    useEffect(() => {
        fetchSettings();
    }, []);

    useEffect(() => {
        // Check if the current default model is still available
        if (
            defaultModel &&
            !availableModels.some((model) => model.value === defaultModel)
        ) {
            setDefaultModel(
                availableModels.length > 0 ? availableModels[0].value : ""
            );
        }
    }, [availableModels, defaultModel]);

    const fetchSettings = async () => {
        try {
            const data = await getSettings();
            const updatedSettings = Object.entries(data.providers).reduce(
                (acc, [provider, settings]) => {
                    acc[provider] = {
                        ...settings,
                        aiModels: settings.aiModels || [],
                    };
                    return acc;
                },
                {}
            );
            setSettings(updatedSettings);
            setCustomApiUrl(data.customApiUrl || "");
            setDefaultModel(data.defaultModel || "");
            setDefaultImageAnalysisModel(data.defaultImageAnalysisModel || "");
            setIsLoading(false);
        } catch (error) {
            console.error("Error fetching settings:", error);
            setSnackbar({
                open: true,
                message: __("Error loading settings", "ai-design-block"),
                severity: "error",
            });
            setIsLoading(false);
        }
    };

    const handleInputChange = (provider, field, value) => {
        setSettings((prevSettings) => ({
            ...prevSettings,
            [provider]: {
                ...prevSettings[provider],
                [field]: value,
            },
        }));
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        await saveSettings();
    };

    const saveSettings = async (settingsToSave = settings) => {
        setIsLoading(true);
        try {
            await updateSettings({
                providers: settingsToSave,
                customApiUrl,
                defaultModel,
                defaultImageAnalysisModel,
            });
            setSnackbar({
                open: true,
                message: __("Settings updated successfully", "ai-design-block"),
                severity: "success",
            });
            setRefreshModelsTrigger((prev) => prev + 1);
        } catch (error) {
            console.error("Error updating settings:", error);
            setSnackbar({
                open: true,
                message: __("Error updating settings", "ai-design-block"),
                severity: "error",
            });
        }
        setIsLoading(false);
    };

    const handleCloseSnackbar = () => {
        setSnackbar({ ...snackbar, open: false });
    };

    const handleToggleProvider = (provider) => {
        if (!settings[provider].enabled && !settings[provider].apiKey) {
            setCurrentProvider(provider);
            setOpenDialog(true);
        } else {
            const newEnabledState = !settings[provider].enabled;
            handleInputChange(provider, "enabled", newEnabledState);

            // If disabling a provider, check if we need to reset the default model
            if (!newEnabledState && defaultModel.startsWith(`${provider}:`)) {
                const newAvailableModels = availableModels.filter(
                    (model) => !model.value.startsWith(`${provider}:`)
                );
                setDefaultModel(
                    newAvailableModels.length > 0
                        ? newAvailableModels[0].value
                        : ""
                );
            }
        }
    };

    const handleApiKeySubmit = async (provider, apiKey) => {
        const updatedSettings = {
            ...settings,
            [provider]: {
                ...settings[provider],
                apiKey: apiKey,
                enabled: true,
            },
        };
        setSettings(updatedSettings);
        await saveSettings(updatedSettings);
    };

    const handleOpenDialog = (provider) => {
        setCurrentProvider(provider);
        setOpenDialog(true);
    };

    const handleAddModel = (provider, model) => {
        setSettings((prevSettings) => {
            const updatedSettings = {
                ...prevSettings,
                [provider]: {
                    ...prevSettings[provider],
                    aiModels: [...prevSettings[provider].aiModels, model],
                },
            };
            return updatedSettings;
        });

        // If this is the first model added, set it as default
        if (availableModels.length === 0) {
            setDefaultModel(`${provider}:${model}`);
        }
    };

    const handleRemoveModel = (provider, modelToRemove) => {
        setSettings((prevSettings) => {
            const updatedSettings = {
                ...prevSettings,
                [provider]: {
                    ...prevSettings[provider],
                    aiModels: prevSettings[provider].aiModels.filter(
                        (model) => model !== modelToRemove
                    ),
                },
            };
            return updatedSettings;
        });

        // If the removed model was the default, reset the default model
        if (defaultModel === `${provider}:${modelToRemove}`) {
            const newAvailableModels = availableModels.filter(
                (model) => model.value !== `${provider}:${modelToRemove}`
            );
            setDefaultModel(
                newAvailableModels.length > 0 ? newAvailableModels[0].value : ""
            );
        }
    };

    const handleTabChange = (event, newValue) => {
        setTabValue(newValue);
    };

    const handleToggleCustomPrompt = (provider) => {
        setSettings((prevSettings) => ({
            ...prevSettings,
            [provider]: {
                ...prevSettings[provider],
                customPromptEnabled:
                    !prevSettings[provider].customPromptEnabled,
            },
        }));
    };

    if (isLoading) {
        return <Typography>{__("Loading...", "ai-design-block")}</Typography>;
    }

    return (
        <Container maxWidth="lg">
            <Paper elevation={3} sx={{ mt: 4 }}>
                <AppBar position="static" color="default" elevation={0}>
                    <Toolbar>
                        <Typography
                            variant="h6"
                            component="div"
                            sx={{ flexGrow: 1 }}
                        >
                            {__("Settings", "ai-design-block")}
                        </Typography>
                        <Button
                            variant="contained"
                            color="primary"
                            onClick={handleSubmit}
                            disabled={isLoading}
                        >
                            {__("Save Settings", "ai-design-block")}
                        </Button>
                    </Toolbar>
                </AppBar>
                <Box sx={{ display: "flex" }}>
                    <Tabs
                        orientation="vertical"
                        variant="scrollable"
                        value={tabValue}
                        onChange={handleTabChange}
                        sx={{
                            borderRight: 1,
                            borderColor: "divider",
                            minWidth: "200px",
                            "& .MuiTab-root": {
                                alignItems: "flex-start",
                                textAlign: "left",
                            },
                        }}
                    >
                        <Tab label={__("Models", "ai-design-block")} />
                        <Tab label={__("Logger", "ai-design-block")} />
                    </Tabs>
                    <Box sx={{ flexGrow: 1 }}>
                        <TabPanel value={tabValue} index={0}>
                            <ModelsSettings
                                settings={settings}
                                customApiUrl={customApiUrl}
                                setCustomApiUrl={setCustomApiUrl}
                                handleInputChange={handleInputChange}
                                handleToggleProvider={handleToggleProvider}
                                handleToggleCustomPrompt={
                                    handleToggleCustomPrompt
                                }
                                refreshModelsTrigger={refreshModelsTrigger}
                                onOpenApiKeyDialog={handleOpenDialog}
                                onAddModel={handleAddModel}
                                onRemoveModel={handleRemoveModel}
                                defaultModel={defaultModel}
                                setDefaultModel={setDefaultModel}
                                defaultImageAnalysisModel={
                                    defaultImageAnalysisModel
                                }
                                setDefaultImageAnalysisModel={
                                    setDefaultImageAnalysisModel
                                }
                                availableModels={availableModels}
                            />
                        </TabPanel>
                        <TabPanel value={tabValue} index={1}>
                            <LoggerSettings />
                        </TabPanel>
                    </Box>
                </Box>
            </Paper>
            <Snackbar
                open={snackbar.open}
                autoHideDuration={6000}
                onClose={handleCloseSnackbar}
            >
                <Alert
                    onClose={handleCloseSnackbar}
                    severity={snackbar.severity}
                    sx={{ width: "100%" }}
                >
                    {snackbar.message}
                </Alert>
            </Snackbar>
            <ApiKeyDialog
                open={openDialog}
                onClose={() => setOpenDialog(false)}
                onSubmit={handleApiKeySubmit}
                provider={currentProvider}
            />
        </Container>
    );
}

export default SettingsPage;
