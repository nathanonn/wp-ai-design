import React, { useState, useEffect } from "react";
import {
  Typography,
  Box,
  Switch,
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogContentText,
  DialogTitle,
  TextField,
  Snackbar,
  Alert,
} from "@mui/material";
import { __ } from "@wordpress/i18n";
import apiFetch from "@wordpress/api-fetch";

function LoggerSettings() {
  const [isLoggerEnabled, setIsLoggerEnabled] = useState(true);
  const [isTableDropped, setIsTableDropped] = useState(false);
  const [openRemoveDialog, setOpenRemoveDialog] = useState(false);
  const [openDropDialog, setOpenDropDialog] = useState(false);
  const [confirmRemove, setConfirmRemove] = useState("");
  const [confirmDrop, setConfirmDrop] = useState("");
  const [snackbar, setSnackbar] = useState({ open: false, message: "", severity: "success" });

  useEffect(() => {
    // Fetch initial logger state
    apiFetch({ path: "/ai-design-block/v1/logger-status" }).then((response) => {
      setIsLoggerEnabled(response.enabled);
      setIsTableDropped(response.tableDropped);
    });
  }, []);

  const handleLoggerToggle = async () => {
    try {
      const response = await apiFetch({
        path: "/ai-design-block/v1/logger-status",
        method: "POST",
        data: { enabled: !isLoggerEnabled },
      });
      setIsLoggerEnabled(response.enabled);
      setSnackbar({ open: true, message: __("Logger status updated", "ai-design-block"), severity: "success" });
    } catch (error) {
      setSnackbar({ open: true, message: __("Failed to update logger status", "ai-design-block"), severity: "error" });
    }
  };

  const handleRemoveLogs = async () => {
    if (confirmRemove.toUpperCase() !== "REMOVE") {
      setSnackbar({ open: true, message: __("Incorrect confirmation", "ai-design-block"), severity: "error" });
      return;
    }
    try {
      await apiFetch({ path: "/ai-design-block/v1/remove-logs", method: "POST" });
      setSnackbar({ open: true, message: __("All logs removed", "ai-design-block"), severity: "success" });
    } catch (error) {
      setSnackbar({ open: true, message: __("Failed to remove logs", "ai-design-block"), severity: "error" });
    }
    setOpenRemoveDialog(false);
    setConfirmRemove("");
  };

  const handleDropTable = async () => {
    if (confirmDrop.toUpperCase() !== "DROP TABLE") {
      setSnackbar({ open: true, message: __("Incorrect confirmation", "ai-design-block"), severity: "error" });
      return;
    }
    try {
      await apiFetch({ path: "/ai-design-block/v1/drop-table", method: "POST" });
      setIsTableDropped(true);
      setSnackbar({ open: true, message: __("Table dropped successfully", "ai-design-block"), severity: "success" });
    } catch (error) {
      setSnackbar({ open: true, message: __("Failed to drop table", "ai-design-block"), severity: "error" });
    }
    setOpenDropDialog(false);
    setConfirmDrop("");
  };

  const handleRecreateTable = async () => {
    try {
      await apiFetch({ path: "/ai-design-block/v1/recreate-table", method: "POST" });
      setIsTableDropped(false);
      setSnackbar({ open: true, message: __("Table recreated successfully", "ai-design-block"), severity: "success" });
    } catch (error) {
      setSnackbar({ open: true, message: __("Failed to recreate table", "ai-design-block"), severity: "error" });
    }
  };

  return (
    <Box sx={{ minHeight: "100vh" }}>
      <Typography variant="h6" gutterBottom>
        {__("Logger Settings", "ai-design-block")}
      </Typography>
      
      <Box sx={{ mb: 2 }}>
        <Typography component="span" sx={{ mr: 1 }}>
          {__("Enable Logger", "ai-design-block")}
        </Typography>
        <Switch checked={isLoggerEnabled} onChange={handleLoggerToggle} />
      </Box>

      <Box sx={{ mb: 2 }}>
        <Button
          variant="contained"
          color="secondary"
          onClick={() => setOpenRemoveDialog(true)}
          disabled={!isLoggerEnabled}
        >
          {__("Remove All Logs", "ai-design-block")}
        </Button>
      </Box>

      {isLoggerEnabled === false && !isTableDropped && (
        <Box sx={{ mb: 2 }}>
          <Button
            variant="contained"
            color="error"
            onClick={() => setOpenDropDialog(true)}
          >
            {__("Drop Table", "ai-design-block")}
          </Button>
        </Box>
      )}

      {isTableDropped && (
        <Box sx={{ mb: 2 }}>
          <Button
            variant="contained"
            color="primary"
            onClick={handleRecreateTable}
          >
            {__("Recreate Table", "ai-design-block")}
          </Button>
        </Box>
      )}

      <Dialog open={openRemoveDialog} onClose={() => setOpenRemoveDialog(false)}>
        <DialogTitle>{__("Remove All Logs", "ai-design-block")}</DialogTitle>
        <DialogContent>
          <DialogContentText>
            {__("To remove all logs, type REMOVE in the field below:", "ai-design-block")}
          </DialogContentText>
          <TextField
            autoFocus
            margin="dense"
            fullWidth
            value={confirmRemove}
            onChange={(e) => setConfirmRemove(e.target.value)}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpenRemoveDialog(false)}>{__("Cancel", "ai-design-block")}</Button>
          <Button onClick={handleRemoveLogs} color="error">{__("Remove", "ai-design-block")}</Button>
        </DialogActions>
      </Dialog>

      <Dialog open={openDropDialog} onClose={() => setOpenDropDialog(false)}>
        <DialogTitle>{__("Drop Table", "ai-design-block")}</DialogTitle>
        <DialogContent>
          <DialogContentText>
            {__("To drop the table, type DROP TABLE in the field below:", "ai-design-block")}
          </DialogContentText>
          <TextField
            autoFocus
            margin="dense"
            fullWidth
            value={confirmDrop}
            onChange={(e) => setConfirmDrop(e.target.value)}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpenDropDialog(false)}>{__("Cancel", "ai-design-block")}</Button>
          <Button onClick={handleDropTable} color="error">{__("Drop", "ai-design-block")}</Button>
        </DialogActions>
      </Dialog>

      <Snackbar open={snackbar.open} autoHideDuration={6000} onClose={() => setSnackbar({ ...snackbar, open: false })}>
        <Alert onClose={() => setSnackbar({ ...snackbar, open: false })} severity={snackbar.severity} sx={{ width: '100%' }}>
          {snackbar.message}
        </Alert>
      </Snackbar>
    </Box>
  );
}

export default LoggerSettings;
