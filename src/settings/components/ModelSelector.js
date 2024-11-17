import { useState, useEffect } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import {
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Button,
} from "@mui/material";
import { getProviderModels } from "../services/api";

function ModelSelector({
  provider,
  value,
  onChange,
  disabled,
  refreshTrigger,
}) {
  const [models, setModels] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (provider && !disabled) {
      fetchModels();
    }
  }, [provider, disabled, refreshTrigger]);

  const fetchModels = async () => {
    setIsLoading(true);
    setError(null);
    try {
      // Wait for 500ms
      await new Promise((resolve) => setTimeout(resolve, 500));
      const fetchedModels = await getProviderModels(provider);
      if (Array.isArray(fetchedModels)) {
        setModels(fetchedModels);
      } else if (typeof fetchedModels === "object" && fetchedModels !== null) {
        // Convert object to array
        setModels(Object.values(fetchedModels));
      } else {
        console.error(
          "Fetched models is not an array or object:",
          fetchedModels
        );
        setError("Invalid response format");
        setModels([]);
      }
    } catch (error) {
      console.error("Error fetching models:", error);
      setError("Failed to fetch models");
      setModels([]);
    }
    setIsLoading(false);
  };

  const handleRefresh = () => {
    fetchModels();
  };

  const getDisplayName = (model) => {
    if (provider === "gemini") {
      return model.replace(/-/g, " ").replace(/(\d+\.\d+)/, "v$1");
    }
    return model;
  };

  return (
    <FormControl
      fullWidth
      margin="normal"
      sx={{
        "& .MuiInputLabel-root": {
          transform: "translate(14px, 16px) scale(1)",
          backgroundColor: "white",
          padding: "0 10px",
        },
        "& .MuiInputLabel-shrink": {
          transform: "translate(14px, -6px) scale(0.75)",
        },
      }}
    >
      <InputLabel>{__("AI Model", "ai-design-block")}</InputLabel>
      <Select
        value={value}
        onChange={(e) => onChange(e.target.value)}
        disabled={disabled || isLoading}
      >
        {isLoading && (
          <MenuItem value="">
            {__("Loading models...", "ai-design-block")}
          </MenuItem>
        )}
        {error && (
          <MenuItem value="">
            {__("Error: ", "ai-design-block") + error}
            <Button onClick={handleRefresh} size="small" sx={{ ml: 1 }}>
              {__("Refresh", "ai-design-block")}
            </Button>
          </MenuItem>
        )}
        {!isLoading && !error && models.length === 0 && (
          <MenuItem value="">
            {__("No models available", "ai-design-block")}
            <Button onClick={handleRefresh} size="small" sx={{ ml: 1 }}>
              {__("Refresh", "ai-design-block")}
            </Button>
          </MenuItem>
        )}
        {!isLoading &&
          !error &&
          models.map((model) => (
            <MenuItem key={model} value={model}>
              {getDisplayName(model)}
            </MenuItem>
          ))}
      </Select>
    </FormControl>
  );
}

export default ModelSelector;
