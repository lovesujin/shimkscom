# shimks.com #

# Orpheus TTS
## Overview
Orpheus TTS is an open-source text-to-speech system built on the Llama-3b backbone. Orpheus demonstrates the emergent capabilities of using LLMs for speech synthesis. We offer comparisons of the models below to leading closed models like Eleven Labs and PlayHT in our blog post.

[Check out our blog post](https://canopylabs.ai/model-releases)


https://github.com/user-attachments/assets/ce17dd3a-f866-4e67-86e4-0025e6e87b8a

## Abilities

- **Human-Like Speech**: Natural intonation, emotion, and rhythm that is superior to SOTA closed source models
- **Zero-Shot Voice Cloning**: Clone voices without prior fine-tuning
- **Guided Emotion and Intonation**: Control speech and emotion characteristics with simple tags
- **Low Latency**: ~200ms streaming latency for realtime applications, reducible to ~100ms with input streaming

## Models

We provide three models in this release, and additionally we offer the data processing scripts and sample datasets to make it very straightforward to create your own finetune.

1. [**Finetuned Prod**](https://huggingface.co/canopylabs/orpheus-tts-0.1-finetune-prod) ‚Äì A finetuned model for everyday TTS applications

2. [**Pretrained**](https://huggingface.co/canopylabs/orpheus-tts-0.1-pretrained) ‚Äì Our base model trained on 100k+ hours of English speech data


### Inference

#### Simple setup on colab
1. [Colab For Tuned Model](https://colab.research.google.com/drive/1KhXT56UePPUHhqitJNUxq63k-pQomz3N?usp=sharing) (not streaming, see below for realtime streaming) ‚Äì A finetuned model for everyday TTS applications.
2. [Colab For Pretrained Model](https://colab.research.google.com/drive/10v9MIEbZOr_3V8ZcPAIh8MN7q2LjcstS?usp=sharing) ‚Äì This notebook is set up for conditioned generation but can be extended to a range of tasks.

#### Streaming Inference Example

1. Clone this repo
   ```bash
   git clone https://github.com/canopyai/Orpheus-TTS.git
   ```
2. Navigate and install packages
   ```bash
   cd Orpheus-TTS && pip install orpheus-speech # uses vllm under the hood for fast inference
   ```
   vllm pushed a slightly buggy version on March 18th so some bugs are being resolved by reverting to `pip install vllm==0.7.3` after `pip install orpheus-speech`
4. Run the example below:
   ```python
   from orpheus_tts import OrpheusModel
   import wave
   import time

   model = OrpheusModel(model_name ="canopylabs/orpheus-tts-0.1-finetune-prod")
   prompt = '''Man, the way social media has, um, completely changed how we interact is just wild, right? Like, we're all connected 24/7 but somehow people feel more alone than ever. And don't even get me started on how it's messing with kids' self-esteem and mental health and whatnot.'''

   start_time = time.monotonic()
   syn_tokens = model.generate_speech(
      prompt=prompt,
      voice="tara",
      )

   with wave.open("output.wav", "wb") as wf:
      wf.setnchannels(1)
      wf.setsampwidth(2)
      wf.setframerate(24000)

      total_frames = 0
      chunk_counter = 0
      for audio_chunk in syn_tokens: # output streaming
         chunk_counter += 1
         frame_count = len(audio_chunk) // (wf.getsampwidth() * wf.getnchannels())
         total_frames += frame_count
         wf.writeframes(audio_chunk)
      duration = total_frames / wf.getframerate()

      end_time = time.monotonic()
      print(f"It took {end_time - start_time} seconds to generate {duration:.2f} seconds of audio")
   ```

#### Prompting

1. The `finetune-prod` models: for the primary model, your text prompt is formatted as `{name}: I went to the ...`. The options for name in order of conversational realism (subjective benchmarks) are "tara", "leah", "jess", "leo", "dan", "mia", "zac", "zoe". Our python package does this formatting for you, and the notebook also prepends the appropriate string. You can additionally add the following emotive tags: `<laugh>`, `<chuckle>`, `<sigh>`, `<cough>`, `<sniffle>`, `<groan>`, `<yawn>`, `<gasp>`.

2. The pretrained model: you can either generate speech just conditioned on text, or generate speech conditioned on one or more existing text-speech pairs in the prompt. Since this model hasn't been explicitly trained on the zero-shot voice cloning objective, the more text-speech pairs you pass in the prompt, the more reliably it will generate in the correct voice.

<!-- 3. The research model: the prompt that should get passed to the model has `prompt + " " + "<{emotion}>"` at the end. It should also not have the `{name}:` prefix as it is only trained on one voice. This model is not designed to be used in production. Rather, it's main goal is to show how LLMs can easily support tags to guide controllable emotional generations, and for now will perform worse on other metrics.
 -->

Additionally, use regular LLM generation args like `temperature`, `top_p`, etc. as you expect for a regular LLM. `repetition_penalty>=1.1`is required for stable generations. Increasing `repetition_penalty` and `temperature` makes the model speak faster.


## Finetune Model

Here is an overview of how to finetune your model on any text and speech.
This is a very simple process analogous to tuning an LLM using Trainer and Transformers.

You should start to see high quality results after ~50 examples but for best results, aim for 300 examples/speaker.

1. Your dataset should be a huggingface dataset in [this format](https://huggingface.co/datasets/canopylabs/zac-sample-dataset)
2. We prepare the data using this [this notebook](https://colab.research.google.com/drive/1wg_CPCA-MzsWtsujwy-1Ovhv-tn8Q1nD?usp=sharing). This pushes an intermediate dataset to your Hugging Face account which you can can feed to the training script in finetune/train.py. Preprocessing should take less than 1 minute/thousand rows.
3. Modify the `finetune/config.yaml` file to include your dataset and training properties, and run the training script. You can additionally run any kind of huggingface compatible process like Lora to tune the model.
   ```bash
    pip install transformers datasets wandb trl flash_attn torch
    huggingface-cli login <enter your HF token>
    wandb login <wandb token>
    accelerate launch train.py
   ```
## Also Check out

While we can't verify these implementations are completely accurate/bug free, they have been recommended on a couple of forums, so we include them here:

1. [A lightweight client for running Orpheus TTS locally using LM Studio API](https://github.com/isaiahbjork/orpheus-tts-local)
2. [Gradio WebUI that runs smoothly on WSL and CUDA](https://github.com/Saganaki22/OrpheusTTS-WebUI)


# Checklist

- [x] Release 3b pretrained model and finetuned models
- [ ] Release pretrained and finetuned models in sizes: 1b, 400m, 150m parameters
- [ ] Fix glitch in realtime streaming package that occasionally skips frames.
- [ ] Fix voice cloning Colab notebook implementation


----------------------------

![Second Me](https://github.com/mindverse/Second-Me/blob/master/images/cover.png)

<div align="center">

[![Homepage](https://img.shields.io/badge/Second_Me-Homepage-blue?style=flat-square&logo=homebridge)](https://www.secondme.io/)
[![Report](https://img.shields.io/badge/Paper-arXiv-red?style=flat-square&logo=arxiv)](https://arxiv.org/abs/2503.08102)
[![Discord](https://img.shields.io/badge/Chat-Discord-5865F2?style=flat-square&logo=discord&logoColor=white)](https://discord.gg/GpWHQNUwrg)
[![Twitter](https://img.shields.io/badge/Follow-@SecondMe_AI-1DA1F2?style=flat-square&logo=x&logoColor=white)](https://x.com/SecondMe_AI1)
[![Reddit](https://img.shields.io/badge/Join-Reddit-FF4500?style=flat-square&logo=reddit&logoColor=white)](https://www.reddit.com/r/SecondMeAI/)

</div>


## Our Vision

Companies like OpenAI built "Super AI" that threatens human independence. We crave individuality: AI that amplifies, not erases, you.

We‚Äôre challenging that with "**Second Me**": an open-source prototype where you craft your own **AI self**‚Äîa new AI species that preserves you, delivers your context, and defends your interests.

It‚Äôs **locally trained and hosted**‚Äîyour data, your control‚Äîyet **globally connected**, scaling your intelligence across an AI network. Beyond that, it‚Äôs your AI identity interface‚Äîa bold standard linking your AI to the world, sparks collaboration among AI selves, and builds tomorrow‚Äôs truly native AI apps.

Join us. Tech enthusiasts, AI pros, domain experts‚ÄîSecond Me is your launchpad to extend your mind into the digital horizon.

## Key Features

### **Train Your AI Self** with AI-Native Memory ([Paper](https://arxiv.org/abs/2503.08102))
Start training your Second Me today with your own memories! Using Hierarchical Memory Modeling (HMM) and the Me-Alignment Algorithm, your AI self captures your identity, understands your context, and reflects you authentically.

 <p align="center">
  <img src="https://github.com/user-attachments/assets/a84c6135-26dc-4413-82aa-f4a373c0ff89" width="94%" />
</p>


### **Scale Your Intelligence** on the Second Me Network
Launch your AI self from your laptop onto our decentralized network‚Äîanyone or any app can connect with your permission, sharing your context as your digital identity.

<p align="center">
  <img src="https://github.com/user-attachments/assets/9a74a3f4-d8fd-41c1-8f24-534ed94c842a" width="94%" />
</p>


### Build Tomorrow‚Äôs Apps with Second Me
**Roleplay**: Your AI self switches personas to represent you in different scenarios.
**AI Space**: Collaborate with other Second Mes to spark ideas or solve problems.

<p align="center">
  <img src="https://github.com/user-attachments/assets/bc6125c1-c84f-4ecc-b620-8932cc408094" width="94%" />
</p>

### 100% **Privacy and Control**
Unlike traditional centralized AI systems, Second Me ensures that your information and intelligence remains local and completely private.



## Getting started & staying tuned with us
Star and join us, and you will receive all release notifications from GitHub without any delay!


 <p align="center">
  <img src="https://github.com/user-attachments/assets/5c14d956-f931-4c25-b0b3-3c2c96cd7581" width="94%" />
</p>

## Quick Start

### Prerequisites
- macOS operating system
- Git installed
- Homebrew (recommended for system dependencies)
- Xcode Command Line Tools (for using make commands)

#### Installing Xcode Command Line Tools
If you haven't installed Xcode Command Line Tools yet, you can install them by running:
```bash
xcode-select --install
```

After installation, you may need to accept the license agreement:
```bash
sudo xcodebuild -license accept
```

### Installation and Setup

1. Clone the repository
```bash
git clone git@github.com:Mindverse/Second-Me.git
cd Second-Me
```

2. Set up the environment

Using make (requires Xcode Command Line Tools):
```bash
make setup
```

Alternatively, you can use the setup script directly:
```bash
./scripts/setup.sh
```

This command will automatically:
- Install all required system dependencies
- Set up Python environment
- Build llama.cpp
- Set up frontend environment

3. Start the service

Using make:
```bash
make start
```

Alternatively, use the script directly:
```bash
./scripts/start.sh
```

4. Access the service
Open your browser and visit `http://localhost:3000`

5. For help and more commands

Using make:
```bash
make help
```
## Tutorial
- Feel free to follow [User tutorial](https://second-me.gitbook.io/a-new-ai-species-making-we-matter-again) to build your Second Me.


## Coming Soon Ì†ΩÌ∫Ä

The following features have been completed internally and are being gradually integrated into the open-source project. For detailed experimental results and technical specifications, please refer to our [Technical Report](https://arxiv.org/abs/2503.08102).

### Ì†ΩÌ¥¨ Model Enhancement Features
- [ ] **Long Chain-of-Thought Training Pipeline**: Enhanced reasoning capabilities through extended thought process training
- [ ] **Direct Preference Optimization for L2 Model**: Improved alignment with user preferences and intent
- [ ] **Data Filtering for Training**: Advanced techniques for higher quality training data selection
- [ ] **Apple Silicon Support**: Native support for Apple Silicon processors with MLX Training and Serving capabilities

### Ì†ΩÌª†Ô∏è Product Features
- [ ] **Natural Language Memory Summarization**: Intuitive memory organization in natural language format


## Contributing

We welcome contributions to Second Me! Whether you're interested in fixing bugs, adding new features, or improving documentation, please check out our Contribution Guide. You can also support Second Me by sharing your experience with it in your community, at tech conferences, or on social media.

For more detailed information about development, please refer to our [Contributing Guide](./CONTRIBUTING.md).

## Contributors

We would like to express our gratitude to all the individuals who have contributed to Second Me! If you're interested in contributing to the future of intelligence uploading, whether through code, documentation, or ideas, please feel free to submit a pull request to our repository: [Second-Me](https://github.com/Mindverse/Second-Me).


<a href="https://github.com/mindverse/Second-Me/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=mindverse/Second-Me" />
</a>

Made with [contrib.rocks](https://contrib.rocks).

## Acknowledgements

This work leverages the power of the open source community.

For data synthesis, we utilized [GraphRAG](https://github.com/microsoft/graphrag) from Microsoft.

For model deployment, we utilized [llama.cpp](https://github.com/ggml-org/llama.cpp), which provides efficient inference capabilities.

Our base models primarily come from the [Qwen2.5](https://huggingface.co/Qwen) series.

We also want to extend our sincere gratitude to all users who have experienced Second Me. We recognize that there is significant room for optimization throughout the entire pipeline, and we are fully committed to iterative improvements to ensure everyone can enjoy the best possible experience locally.

## License

Second Me is open source software licensed under the Apache License 2.0. See the [LICENSE](LICENSE) file for more details.

[license]: ./LICENSE


