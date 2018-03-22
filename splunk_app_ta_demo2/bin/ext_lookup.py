#!/usr/bin/env python
import os, sys, subprocess

os.environ["LD_LIBRARY_PATH"] = ""

sys.exit(subprocess.call(['/usr/bin/php', sys.argv[0].replace('.py', '.php')] + sys.argv[1:]))