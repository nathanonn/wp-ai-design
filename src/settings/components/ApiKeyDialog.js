import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
} from "@mui/material";
import ApiKeyField from "./ApiKeyField";

function ApiKeyDialog({ open, onClose, onSubmit, provider }) {
  const [tempApiKey, setTempApiKey] = useState("");

  const handleSubmit = () => {
    onSubmit(provider, tempApiKey);
    setTempApiKey("");
    onClose();
  };

  const handleClose = () => {
    setTempApiKey("");
    onClose();
  };

  return (
    <Dialog maxWidth="md" open={open} onClose={handleClose}>
      <DialogTitle>{__("Enter API Key", "ai-design-block")}</DialogTitle>
      <DialogContent>
        <ApiKeyField
          provider={provider}
          apiKey={tempApiKey}
          onChange={setTempApiKey}
        />
      </DialogContent>
      <DialogActions>
        <Button onClick={handleClose}>{__("Cancel", "ai-design-block")}</Button>
        <Button onClick={handleSubmit} disabled={!tempApiKey}>
          {__("Submit", "ai-design-block")}
        </Button>
      </DialogActions>
    </Dialog>
  );
}

export default ApiKeyDialog;
