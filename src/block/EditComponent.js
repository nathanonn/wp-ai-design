import { useDispatch } from "@wordpress/data";
import {
    useBlockProps,
    MediaUpload,
    MediaUploadCheck,
} from "@wordpress/block-editor";
import {
    TextareaControl,
    Button,
    Spinner,
    SelectControl,
    RadioControl,
    DropZone,
    CheckboxControl,
    Snackbar,
} from "@wordpress/components";
import apiFetch from "@wordpress/api-fetch";
import { useState, useEffect, useRef } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { createBlock } from "@wordpress/blocks";
import {
    convertResponseToBlocksArray,
    convertResponseStringToArray,
    getAvailableBlockNames,
} from "./utils";

function EditComponent({ attributes, setAttributes, clientId }) {
    const blockProps = useBlockProps();
    const [isLoading, setIsLoading] = useState(false);
    const { replaceBlock } = useDispatch("core/block-editor");
    const textareaRef = useRef(null);
    const [availableModels, setAvailableModels] = useState([]);
    const [designMode, setDesignMode] = useState("text");
    const [selectedImageAnalysisModel, setSelectedImageAnalysisModel] =
        useState("");
    const [analyzeImage, setAnalyzeImage] = useState("yes");
    const [error, setError] = useState(null);
    const [invalidJsonResponse, setInvalidJsonResponse] = useState(null);
    const [fixedJson, setFixedJson] = useState(""); // New state for fixed JSON

    useEffect(() => {
        if (textareaRef.current) {
            textareaRef.current.focus();
        }
        fetchSettings();
    }, []);

    const fetchSettings = async () => {
        try {
            const settings = await apiFetch({
                path: "/ai-design-block/v1/block-settings",
            });
            const models = Object.entries(settings.providers).flatMap(
                ([provider, providerSettings]) => {
                    if (
                        providerSettings.enabled &&
                        providerSettings.aiModels.length > 0
                    ) {
                        return providerSettings.aiModels.map((model) => ({
                            value: `${provider}_|_${model}`,
                            label: `${model}`,
                        }));
                    }
                    return [];
                }
            );
            setAvailableModels(models);
            // Set the default model only if not already set
            if (!attributes.selectedProvider || !attributes.selectedModel) {
                if (settings.defaultModel) {
                    const [defaultProvider, defaultModel] =
                        settings.defaultModel.split("_|_");
                    setAttributes({
                        selectedProvider: defaultProvider,
                        selectedModel: defaultModel,
                    });
                } else if (models.length > 0) {
                    const [defaultProvider, defaultModel] =
                        models[0].value.split("_|_");
                    setAttributes({
                        selectedProvider: defaultProvider,
                        selectedModel: defaultModel,
                    });
                }
            }

            setSelectedImageAnalysisModel(
                settings.defaultImageAnalysisModel || ""
            );
        } catch (error) {
            console.error("Error fetching settings:", error);
        }
    };

    const generatePattern = () => {
        setIsLoading(true);
        setError(null);
        // If there's fixedJson, use it instead of fetching from API
        const fetchData =
            invalidJsonResponse && fixedJson
                ? fixedJson
                : {
                      content: designMode === "text" ? attributes.content : "",
                      provider: attributes.selectedProvider,
                      model: attributes.selectedModel,
                      image_url:
                          designMode === "image" ? attributes.imageUrl : "",
                      image_analysis_model: analyzeImage
                          ? selectedImageAnalysisModel
                          : "",
                      analyze_image: analyzeImage,
                  };

        // If using fixedJson, parse it directly
        if (invalidJsonResponse && fixedJson) {
            try {
                const parsedFixedJson = JSON.parse(fixedJson);
                processResponse(parsedFixedJson);
                setInvalidJsonResponse(null); // Clear invalid JSON state after successful processing
            } catch (error) {
                setError(
                    "The fixed JSON is still invalid. Please ensure it is correctly formatted."
                );
                console.error("Error parsing fixed JSON:", error);
                setIsLoading(false);
            }
            return;
        }

        apiFetch({
            path: "/ai-design-block/v1/generate",
            method: "POST",
            data: fetchData,
        })
            .then((response) => {
                console.log("Response received:", response);
                if (typeof response === "string") {
                    try {
                        response = convertResponseStringToArray(response);
                    } catch (error) {
                        // If JSON repair fails, show the broken JSON to user
                        setInvalidJsonResponse(response);
                    }
                }
                processResponse(response);
            })
            .catch((error) => {
                console.error("Error generating pattern:", error);
                setError(
                    error.message ||
                        "An error occurred while generating the pattern."
                );
            })
            .finally(() => {
                setIsLoading(false);
            });
    };

    const processResponse = (response) => {
        const blocksArray = convertResponseToBlocksArray(response);
        const newBlocks = processApiResponse(blocksArray);
        if (newBlocks.length > 0) {
            replaceBlock(clientId, newBlocks);
        }
    };

    const processApiResponse = (blocks) => {
        const availableBlockNames = getAvailableBlockNames();

        const createBlocks = (blocks, availableBlockNames) => {
            return blocks
                .filter((block) => availableBlockNames.includes(block.name))
                .map((block) => {
                    const innerBlocks = block.innerBlocks
                        ? createBlocks(block.innerBlocks, availableBlockNames)
                        : [];
                    const attributes = { ...block.attributes };

                    return createBlock(block.name, attributes, innerBlocks);
                });
        };
        return createBlocks(blocks, availableBlockNames);
    };

    const handleKeyDown = (event) => {
        if (event.key === "Enter" && !event.shiftKey) {
            event.preventDefault();
            generatePattern();
        }
    };

    const removeImage = () => {
        setAttributes({ imageUrl: null });
    };

    const handleImageUpload = (media) => {
        setAttributes({ imageUrl: media.url });
    };

    const clearError = () => {
        setError(null);
    };

    useEffect(() => {
        // disable this for now
        // if (error) {
        //   const timer = setTimeout(() => {
        //     setError(null);
        //   }, 4000);
        //   return () => clearTimeout(timer);
        // }
    }, [error]);

    return (
        <div {...blockProps} className="ai-design-block-container">
            {error && <Snackbar onRemove={clearError}>{error}</Snackbar>}
            {invalidJsonResponse ? (
                <div className="invalid-json-response">
                    <h3>{__("Invalid JSON Response", "ai-design-block")}</h3>
                    <p>
                        {__(
                            "The API returned invalid JSON. Please follow the steps below to fix it:",
                            "ai-design-block"
                        )}
                    </p>

                    {/* Broken JSON Display with Copy Button */}
                    <div className="broken-json-section">
                        <h4>{__("Broken JSON:", "ai-design-block")}</h4>
                        <TextareaControl
                            value={invalidJsonResponse}
                            readOnly
                            rows={10}
                            style={{
                                backgroundColor: "#f8d7da",
                                borderColor: "#f5c6cb",
                            }}
                        />
                        <Button
                            variant="secondary"
                            onClick={() => {
                                navigator.clipboard.writeText(
                                    invalidJsonResponse
                                );
                            }}
                        >
                            {__("Copy Broken JSON", "ai-design-block")}
                        </Button>
                    </div>

                    {/* Instructions */}
                    <div className="instructions-section">
                        <h4>{__("How to Fix the JSON:", "ai-design-block")}</h4>
                        <ol>
                            <li>
                                {__(
                                    "Copy the broken JSON using the button above.",
                                    "ai-design-block"
                                )}
                            </li>
                            <li>
                                {__("Go to ", "ai-design-block")}
                                <a
                                    href="https://jsoneditoronline.org/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {__(
                                        "JSON Editor Online",
                                        "ai-design-block"
                                    )}
                                </a>
                                {__(
                                    " and paste the JSON into the editor.",
                                    "ai-design-block"
                                )}
                            </li>
                            <li>
                                {__(
                                    "Fix any errors in the JSON to make it valid.",
                                    "ai-design-block"
                                )}
                            </li>
                            <li>
                                {__(
                                    "Copy the fixed JSON from the editor.",
                                    "ai-design-block"
                                )}
                            </li>
                            <li>
                                {__(
                                    "Paste it into the textarea below and click the Generate button.",
                                    "ai-design-block"
                                )}
                            </li>
                        </ol>
                    </div>

                    {/* Fixed JSON Input */}
                    <div className="fixed-json-section">
                        <h4>{__("Paste Fixed JSON:", "ai-design-block")}</h4>
                        <TextareaControl
                            value={fixedJson}
                            onChange={(value) => setFixedJson(value)}
                            rows={10}
                            placeholder={__(
                                "Paste the fixed JSON here...",
                                "ai-design-block"
                            )}
                        />
                    </div>

                    {/* Generate Button */}
                    <div
                        style={{
                            display: "flex",
                            justifyContent: "space-between",
                        }}
                    >
                        <Button
                            variant="primary"
                            onClick={generatePattern}
                            disabled={isLoading || !fixedJson}
                            className="retry-generate-button"
                        >
                            {isLoading ? (
                                <Spinner />
                            ) : (
                                __("Generate Blocks", "ai-design-block")
                            )}
                        </Button>
                        <Button
                            variant="secondary"
                            onClick={() => {
                                setInvalidJsonResponse(null);
                                setFixedJson("");
                            }}
                        >
                            {__("Reset", "ai-design-block")}
                        </Button>
                    </div>
                </div>
            ) : (
                <>
                    <RadioControl
                        label={__("Mode", "ai-design-block")}
                        selected={designMode}
                        disabled={isLoading}
                        options={[
                            {
                                label: __("Text to Design", "ai-design-block"),
                                value: "text",
                            },
                            {
                                label: __("Image to Design", "ai-design-block"),
                                value: "image",
                            },
                        ]}
                        className="ai-design-design-mode"
                        onChange={(value) => setDesignMode(value)}
                    />
                    {designMode === "text" && (
                        <div className="ai-design-input-wrapper">
                            <TextareaControl
                                ref={textareaRef}
                                value={attributes.content}
                                disabled={isLoading}
                                onChange={(content) =>
                                    setAttributes({ content })
                                }
                                placeholder={__(
                                    "Create a 3-columns card layout",
                                    "ai-design-block"
                                )}
                                onKeyDown={handleKeyDown}
                            />
                        </div>
                    )}
                    {designMode === "image" && (
                        <div className="ai-design-image-upload-wrapper">
                            {!attributes.imageUrl && (
                                <MediaUploadCheck>
                                    <MediaUpload
                                        onSelect={handleImageUpload}
                                        allowedTypes={["image"]}
                                        render={({ open }) => (
                                            <div
                                                className="ai-design-dropzone"
                                                onClick={open}
                                                style={{
                                                    border: "2px dashed #ccc",
                                                    padding: "20px",
                                                    textAlign: "center",
                                                    cursor: "pointer",
                                                }}
                                            >
                                                <p>
                                                    {__(
                                                        "Drop an image here or click to select",
                                                        "ai-design-block"
                                                    )}
                                                </p>
                                                <DropZone
                                                    onFilesDrop={(files) => {
                                                        const file = files[0];
                                                        if (
                                                            file.type.startsWith(
                                                                "image/"
                                                            )
                                                        ) {
                                                            const formData =
                                                                new FormData();
                                                            formData.append(
                                                                "file",
                                                                file
                                                            );
                                                            apiFetch({
                                                                path: "/wp/v2/media",
                                                                method: "POST",
                                                                body: formData,
                                                            }).then(
                                                                (response) => {
                                                                    handleImageUpload(
                                                                        response
                                                                    );
                                                                }
                                                            );
                                                        }
                                                    }}
                                                />
                                            </div>
                                        )}
                                    />
                                </MediaUploadCheck>
                            )}
                            {attributes.imageUrl && (
                                <div className="selected-image-wrapper">
                                    <img
                                        src={attributes.imageUrl}
                                        alt={__(
                                            "Selected Image",
                                            "ai-design-block"
                                        )}
                                        style={{ maxWidth: "300px" }}
                                    />
                                    <Button
                                        onClick={removeImage}
                                        variant="secondary"
                                        disabled={isLoading}
                                        className="remove-image-button"
                                    >
                                        {__("Remove", "ai-design-block")}
                                    </Button>
                                </div>
                            )}
                        </div>
                    )}
                    {designMode === "text" && (
                        <p className="hint-text">
                            {__(
                                "Press Shift+Enter for a new line, Enter to generate",
                                "ai-design-block"
                            )}
                        </p>
                    )}
                    <div className="ai-design-options-wrapper">
                        <div className="ai-design-select-wrapper">
                            <SelectControl
                                value={`${attributes.selectedProvider}_|_${attributes.selectedModel}`}
                                options={availableModels}
                                disabled={isLoading}
                                onChange={(value) => {
                                    const [provider, model] =
                                        value.split("_|_");
                                    setAttributes({
                                        selectedProvider: provider,
                                        selectedModel: model,
                                    });
                                }}
                            />
                            {designMode === "image" && (
                                <CheckboxControl
                                    label={__(
                                        "Analyze uploaded image",
                                        "ai-design-block"
                                    )}
                                    disabled={isLoading}
                                    checked={analyzeImage === "yes"}
                                    onChange={(value) =>
                                        setAnalyzeImage(value ? "yes" : "no")
                                    }
                                />
                            )}
                        </div>
                        <Button
                            variant="primary"
                            onClick={generatePattern}
                            disabled={
                                isLoading ||
                                (designMode === "image" && !attributes.imageUrl)
                            }
                            className="generate-button"
                        >
                            {isLoading ? (
                                <Spinner />
                            ) : (
                                __("Generate", "ai-design-block")
                            )}
                        </Button>
                    </div>
                </>
            )}
        </div>
    );
}

export default EditComponent;
